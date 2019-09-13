# -*- mode: ruby -*-
# vi: set ft=ruby :

@script = <<SCRIPT
apt-get update
apt-get install -y apache2 git curl php  php-cli php-curl php-intl php-json php-zip php-dev php-mysql php-mbstring php-xdebug unzip

# Install Developer tools
apt-get install -y composer php-pear
pear install PHP_CodeSniffer

# Configure xdebug
echo "
display_errors=1

[xdebug]
xdebug.default_enable=1
xdebug.idekey=PHPSTORM
xdebug.remote_enable=1
xdebug.remote_autostart=1
xdebug.remote_port=9000
xdebug.remote_connect_back=1
xdebug.profiler_enable=0
xdebug.profiler_output_dir=/var/www/profiler" >> /etc/php/7.2/apache2/php.ini

if [ -e /usr/local/bin/composer ]; then
    /usr/local/bin/composer self-update
else
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Reset home directory of vagrant user
if ! grep -q "cd /vagrant" /home/vagrant/.profile; then
    echo "cd /vagrant" >> /home/vagrant/.profile
fi

SCRIPT

Vagrant.configure('2') do |config|
  if Vagrant.has_plugin?("vagrant-vbguest")
    config.vbguest.auto_update = false  
  end

  config.vm.box = 'bento/ubuntu-18.04'
  config.vm.synced_folder '.', '/vagrant'
  config.vm.provision 'shell', inline: @script

  config.vm.provider "virtualbox" do |vb|
    vb.customize ["modifyvm", :id, "--memory", "2048"]
    vb.customize ["modifyvm", :id, "--name", "Expression Project"]
  end
end
