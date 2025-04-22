Troubleshooting


# Errors

**Symfony Exception**
`Vite manifest not found at: /srv/koel/www/public/build/manifest.json (View: /srv/koel/www/resources/views/index.blade.php)`

**Explanation**
Vite needs to build its assets. This can be done by either executing 
`npm run build` 
or 
`node /srv/koel/www/node_modules/vite/bin/vite.js build` 

**Solution:**

```bash
sudo su -l koel
cd /srv/koel/www
npm install
npm audit fix
npm install # yes again
npm run build
```



File does not exist at path /srv/koel/www/.version