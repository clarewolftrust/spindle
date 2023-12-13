read -p "Username? " username
rsync -azP --delete --exclude={'node_modules','.*','storage'} . $username@playspindle.com:playspindle.com