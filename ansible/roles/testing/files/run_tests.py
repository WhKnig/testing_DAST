import os
import subprocess
import argparse
from datetime import datetime

def get_scan_cmd(scanner, target_ip, result_file):
    url = f"http://{target_ip}"
    if scanner == "zap":
        # ZAP command: quick scan, xml output
        return f"/snap/bin/zaproxy -cmd -quickurl {url} -quickout {result_file}.xml"
    elif scanner == "arachni":
        # Arachni command: specific checks, afr output
        return f"arachni {url} --checks=sql_injection,xss,csrf,path_traversal,os_cmd_injection --report-save-path {result_file}.afr"
    elif scanner == "wapiti":
        # Wapiti command: specific modules, xml output. 
        # Added timeout control just in case.
        return f"wapiti -u {url} -m sql,xss,csrf,exec,file -f xml -o {result_file}.xml --flush-session"
    elif scanner == "skipfish":
        # Skipfish: Output dir must not exist.
        return f"skipfish -o {result_file}_dir -S /usr/share/skipfish/dictionaries/minimal.wl {url}"
    return ""

def run_command(cmd):
    print(f"Executing: {cmd}")
    try:
        # 10 minute timeout per scan
        subprocess.run(cmd, shell=True, check=False, timeout=600)
    except subprocess.TimeoutExpired:
        print("Command timed out!")

def main():
    parser = argparse.ArgumentParser(description='Run a single security scan.')
    parser.add_argument('--scanner', required=True, choices=['zap', 'wapiti', 'skipfish', 'arachni'])
    parser.add_argument('--target_name', required=True)
    parser.add_argument('--target_ip', required=True)
    parser.add_argument('--run_id', default='1')
    
    args = parser.parse_args()
    
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    # Save to /vagrant/results directly to persist on host
    result_base = f"/vagrant/results/{args.scanner}/{args.target_name}"
    os.makedirs(result_base, exist_ok=True)
    
    result_file = f"{result_base}/run_{args.run_id}_{timestamp}"
    
    print(f"--- Starting Scan: {args.scanner} against {args.target_name} ({args.target_ip}) ---")
    cmd = get_scan_cmd(args.scanner, args.target_ip, result_file)
    
    if cmd:
        run_command(cmd)
        
        # Post-processing for Arachni
        if args.scanner == "arachni":
            print("Generating Arachni JSON report...")
            report_cmd = f"arachni_reporter {result_file}.afr --reporter=json:outfile={result_file}.json"
            run_command(report_cmd)
    else:
        print(f"Unknown scanner: {args.scanner}")

if __name__ == "__main__":
    main()
