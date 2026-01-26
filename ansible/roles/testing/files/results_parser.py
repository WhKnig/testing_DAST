import os
import xml.etree.ElementTree as ET
import json
import re
import csv

# --- Ground Truth Definition ---
# Mapping standard vulnerability keys to expected presence (True)
# Keys: sql_injection, xss, csrf, path_traversal, nosql_injection, insecure_deserialization, command_injection
GROUND_TRUTH = {
    "ubuntu": { # App1 (PHP/MySQL)
        "sql_injection": True, "xss": True, "csrf": True
    },
    "uduntu1": { # App2 (Flask/SQLite)
        "sql_injection": True, "xss": True, "csrf": True, "path_traversal": True
    },
    "uduntu2": { # App3 (Node/Postgres)
        "sql_injection": True, "xss": True, "csrf": True, "insecure_deserialization": True
    },
    "uduntu3": { # App4 (Ruby/Mongo)
        "nosql_injection": True, "xss": True, "csrf": True, "command_injection": True
    }
}

# --- Mapping Helper ---
def map_vuln_type(raw_name):
    """Maps scanner-specific vulnerability names to our standard ground truth keys."""
    n = raw_name.lower()
    if "sql" in n and "injection" in n: return "sql_injection"
    if "cross" in n and "scripting" in n: return "xss"
    if "xss" in n: return "xss"
    if "csrf" in n or "forgery" in n: return "csrf"
    if "traversal" in n or "directory" in n: return "path_traversal"
    if "deserialization" in n: return "insecure_deserialization"
    if "command" in n and "injection" in n: return "command_injection"
    if "nosql" in n: return "nosql_injection"
    if "remote file inclusion" in n: return "rfi" # Extra
    return "other"

# --- Parsers ---

def parse_zap(path):
    found = set()
    try:
        tree = ET.parse(path)
        for alert in tree.findall(".//alertitem"):
            name = alert.find("alert").text
            v_type = map_vuln_type(name)
            if v_type != "other":
                found.add(v_type)
    except Exception as e:
        print(f"Error parsing ZAP {path}: {e}")
    return found

def parse_wapiti(path):
    found = set()
    try:
        tree = ET.parse(path)
        for vuln in tree.findall(".//vulnerability"):
            name = vuln.get("name")
            v_type = map_vuln_type(name)
            if v_type != "other":
                found.add(v_type)
    except Exception as e:
        print(f"Error parsing Wapiti {path}: {e}")
    return found

def parse_arachni(path):
    found = set()
    try:
        with open(path, 'r') as f:
            data = json.load(f)
            for issue in data.get("issues", []):
                name = issue.get("name")
                v_type = map_vuln_type(name)
                if v_type != "other":
                    found.add(v_type)
    except Exception as e:
        print(f"Error parsing Arachni {path}: {e}")
    return found

def parse_skipfish(path):
    found = set()
    # Skipfish creates an index.html. We can grep it for issue types or parse report.js if it exists.
    # index.html usually contains text like "SQL injection"
    try:
        # Looking for index.html inside the _dir folder
        index_path = os.path.join(path, "index.html")
        if not os.path.exists(index_path): return found
        
        with open(index_path, 'r', errors='ignore') as f:
            content = f.read()
            # Simple keyword matching for Skipfish as generic parsing is hard without a structured file
            # Skipfish reports are visual HTMLs.
            if "SQL injection" in content: found.add("sql_injection")
            if "Cross-site scripting" in content or "XSS" in content: found.add("xss")
            if "Cross-site request forgery" in content: found.add("csrf")
            if "Directory traversal" in content: found.add("path_traversal")
            if "Shell injection" in content: found.add("command_injection")
            if "Format string" in content: pass # Ignore
    except Exception as e:
        print(f"Error parsing Skipfish {path}: {e}")
    return found

# --- Main Logic ---

def main():
    results_dir = "/results"
    output_rows = []
    
    # Header
    output_rows.append(["Scanner", "Environment", "Vulnerability", "TP", "FP", "FN"])

    # Iterate over available results
    # Structure: /results/<scanner>/<target>/<file>
    if not os.path.exists(results_dir):
        print("No results directory found.")
        return

    for scanner in os.listdir(results_dir):
        scanner_path = os.path.join(results_dir, scanner)
        if not os.path.isdir(scanner_path): continue
        
        for target in os.listdir(scanner_path):
            target_path = os.path.join(scanner_path, target)
            if not os.path.isdir(target_path): continue
            
            # Aggregate findings from all runs for this target/scanner (simulate one big result)
            all_found_vulns = set()
            
            for file in os.listdir(target_path):
                file_path = os.path.join(target_path, file)
                
                if scanner == "zap" and file.endswith(".xml"):
                    all_found_vulns.update(parse_zap(file_path))
                elif scanner == "wapiti" and file.endswith(".xml"):
                    all_found_vulns.update(parse_wapiti(file_path))
                elif scanner == "arachni" and file.endswith(".json"):
                    all_found_vulns.update(parse_arachni(file_path))
                elif scanner == "skipfish" and file.endswith("_dir"):
                    all_found_vulns.update(parse_skipfish(file_path))
            
            # Compare with Ground Truth
            expected_vulns = GROUND_TRUTH.get(target, {})
            # We care about all specific types we track.
            # Get union of all expected and mapped types to iterate cleanly
            # Or just iterate over the standard set of keys if we want a fixed matrix
            
            tracked_vulns = [
                "sql_injection", "xss", "csrf", "path_traversal", 
                "nosql_injection", "insecure_deserialization", "command_injection"
            ]

            for v_type in tracked_vulns:
                tp = 0
                fp = 0
                fn = 0
                
                is_expected = expected_vulns.get(v_type, False)
                is_found = v_type in all_found_vulns
                
                if is_expected and is_found:
                    tp = 1
                elif not is_expected and is_found:
                    fp = 1
                elif is_expected and not is_found:
                    fn = 1
                # Else: True Negative (not expected, not found) - user didn't ask for TN but it's implicit
                
                # Add row
                # Environments match VM names: ubuntu=App1, uduntu1=App2, etc.
                env_map = {
                    "ubuntu": "App1 (PHP)",
                    "uduntu1": "App2 (Python)",
                    "uduntu2": "App3 (Node)",
                    "uduntu3": "App4 (Ruby)"
                }
                env_name = env_map.get(target, target)
                
                output_rows.append([scanner, env_name, v_type, tp, fp, fn])

    # Write CSV
    with open("/vagrant/results/results.csv", "w", newline='') as f:
        writer = csv.writer(f)
        writer.writerows(output_rows)
    
    print("CSV report generated at /vagrant/results/results.csv")

if __name__ == "__main__":
    main()
