This is the permenant public key file for the batea server. 
There is another set of keys, in storage/keys/ that is overwritten every 24 hours
Those keys permit encrypted data to be temporarily read assuming you know the a given browser token..
But after 24 hours they are replaced and permenantly deleted...

This is the key that enabled long term reading. 
Its corresponding private key has been moved off-server (obviously that is the whole point)...

if you create your own instance and you are copying config_template to config remember that you need to create your own keys.
A good way to do this is to let the server automatically generate them for you, by just copying your first days 
temporary keys (private key off server, public key here and in /public/ for backup purposes). 

If you leave this public key in place, then only docgraph will be able to unlock your data, and we are unlikely to help you with that.

Also when you create the file, make sure you use chmod -w and chmod g-w to ensure that the file is not writeable even by its owner.
Then use chattr +i to ensure that no further changes can be made accidentally. 
http://unix.stackexchange.com/questions/67508/how-do-i-make-a-file-not-modifiable

You want to do everything possible to ensure that this file is not overwritten by the web server or any other remote file-write vulerability.
It would be pretty simple to have a situation where extension users were donating using a public key that no one had access to.

Also be careful with the keys in /app/storage/keys , which are intentionally writeable by the apache user.
It might be possible to make it so that these files were not overwritten nightly, and the private key for that pair is exposed.

Eventually, I might decide that this latest risk is to great and end the "recent requests" API decryption feature. 
But right now I am pretty nervous about losing data and not being sure things are working correctly. 

Another risk is that the long-term decryption for the project might not work, which means that all of generous donations from our 
users will be lost. 

Aint encryption fun!!!

-FT

