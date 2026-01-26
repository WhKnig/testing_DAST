Vagrant.configure("2") do |config|
  # Kali Host (Control Node)
  config.vm.define "kali" do |kali|
    kali.vm.box = "kalilinux/rolling"
    kali.vm.hostname = "kali"
    kali.vm.network "private_network", ip: "192.168.56.10"
    kali.vm.provider "virtualbox" do |vb|
      vb.memory = "4096"
      vb.cpus = 2
      vb.name = "testing-kali"
    end
  end

  # Ubuntu 22.04 (Target 1)
  config.vm.define "ubuntu" do |ubuntu|
    ubuntu.vm.box = "ubuntu/jammy64"
    ubuntu.vm.hostname = "ubuntu"
    ubuntu.vm.network "private_network", ip: "192.168.56.11"
    ubuntu.vm.provider "virtualbox" do |vb|
      vb.memory = "1024"
      vb.cpus = 1
      vb.name = "testing-ubuntu"
    end
  end

  # Target 2 (was uduntu1, now Ubuntu)
  config.vm.define "uduntu1" do |uduntu1|
    uduntu1.vm.box = "ubuntu/jammy64"
    uduntu1.vm.hostname = "uduntu1"
    uduntu1.vm.network "private_network", ip: "192.168.56.12"
    uduntu1.vm.provider "virtualbox" do |vb|
      vb.memory = "1024"
      vb.cpus = 1
      vb.name = "testing-uduntu1"
    end
  end

  # Target 3 (was uduntu2, now Ubuntu)
  config.vm.define "uduntu2" do |uduntu2|
    uduntu2.vm.box = "ubuntu/jammy64"
    uduntu2.vm.hostname = "uduntu2"
    uduntu2.vm.network "private_network", ip: "192.168.56.13"
    uduntu2.vm.provider "virtualbox" do |vb|
      vb.memory = "1024"
      vb.cpus = 1
      vb.name = "testing-uduntu2"
    end
  end

  # Target 4 (was uduntu3, now Ubuntu)
  config.vm.define "uduntu3" do |uduntu3|
    uduntu3.vm.box = "ubuntu/jammy64"
    uduntu3.vm.hostname = "uduntu3"
    uduntu3.vm.network "private_network", ip: "192.168.56.14"
    uduntu3.vm.provider "virtualbox" do |vb|
      vb.memory = "1024"
      vb.cpus = 1
      vb.name = "testing-uduntu3"
    end
    
    # Provision everything from the last machine to ensure all targets are up
    uduntu3.vm.provision "ansible" do |ansible|
      ansible.playbook = "ansible/site.yml"
      ansible.compatibility_mode = "2.0"
      ansible.limit = "all"
      ansible.groups = {
        "scanners" => ["kali"],
        "targets" => ["ubuntu", "uduntu1", "uduntu2", "uduntu3"]
      }
    end
  end

  # Global settings
  config.vm.synced_folder ".", "/vagrant", type: "rsync"
  config.ssh.insert_key = false
end