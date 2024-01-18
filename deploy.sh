yarn build
read -p "Username? " username
rsync -azP --delete --exclude={'node_modules','.*','storage','public/hot'} . $username@playspindle.com:playspindle.com
