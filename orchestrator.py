import subprocess
import time

TARGETS = {
    "ubuntu": "192.168.56.11",
    "uduntu1": "192.168.56.12",
    "uduntu2": "192.168.56.13",
    "uduntu3": "192.168.56.14"
}

SCANNERS = ["zap", "wapiti", "arachni", "skipfish"]

def run_local(cmd):
    print(f"RUNNING ON HOST: {cmd}")
    subprocess.run(cmd, shell=True, check=True)

def orchestrate():
    print("Creating 'clean_state' snapshots for all targets...")
    for target in TARGETS.keys():
        try:
            run_local(f"vagrant snapshot save {target} clean_state --force")
        except Exception as e:
            print(f"Failed to snapshot {target}: {e}")

    for scanner in SCANNERS:
        for target, ip in TARGETS.items():
            for run_num in range(1, 11): 
                scan_id = run_num
                print(f"\n=== Starting Job: {scanner} vs {target} (Run {run_num}/10) ===")
                
                print(f"Restoring {target} to clean state...")
                try:
                    run_local(f"vagrant snapshot restore {target} clean_state")
                    time.sleep(10) 
                except Exception as e:
                    print(f"Failed to restore {target}: {e}")
                    continue

                print(f"Executing scan inside Kali...")
                scan_cmd = (
                    f"python3 /home/vagrant/scripts/run_tests.py "
                    f"--scanner {scanner} "
                    f"--target_name {target} "
                    f"--target_ip {ip} "
                    f"--run_id {scan_id}"
                )
                
                try:
                    run_local(f"vagrant ssh kali -c \"{scan_cmd}\"")
                except Exception as e:
                    print(f"Scan failed/timeout: {e}")

if __name__ == "__main__":
    orchestrate()
