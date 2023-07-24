# [timotijhof.net](https://timotijhof.net)

## Credits

* The theme is based on [plainwhite-jekyll](https://github.com/thelehhman/plainwhite-jekyll)
by Samarjeet Singh.

## See also

* https://developer.wordpress.org/themes/basics/template-hierarchy/

## Deployment

### One-time remote setup

_Inspired by <http://toroid.org/git-website-howto>._

**Step 1**: Create Git repository, on your server:

```
cd ~/git/
mkdir wp-theme-ttnet.git && cd wp-theme-ttnet.git
git init --bare
# Enable symlinks (I use this in _plugins/symlink for my subdomains)
git config core.symlinks true
```

**Step 2**: Create a plain directory, on your server, to be the recipient of deployments:

```
cd ~/git-deploy/
mkdir wp-theme-ttnet
```

**Step 3**: Create a `post-receive` hook in the Git repository. This hook is a shell script that the server will execute after you push a Git commit to it. The script will make sure your deployment directory will become a checkout of the latest commit. Remember to mark the `post-receive` file as executable!

```
~/git/wp-theme-ttnet/$ cat hooks/post-receive
```
```
#!/bin/sh
export GIT_WORK_TREE=/home/deb35044/git-deploy/wp-theme-ttnet/

# Workaround bad sshd/umask default (RHEL8, Antagonist).
echo "[krinkle] setting umask to 0002..."
umask 0002

git checkout -f
```
```
~/git/wp-theme-ttnet$ chmod +x hooks/post-receive
```

**Done!** You can now push to this repository, and a static copy of it will be maintained automatically in the git-deploy directory. I use this method (instead of simply exposing the Git repository from my web server) so that `.git` is naturally not exposed, and so that there is no Git "working copy" to worry about, which could sometimes get dirty or stuck for some reason.

You would then symlink to `~/git-deploy/wp-theme-ttnet/` from the appropiate place under `public_html/wordpress/wp-content/`.

### One-time local setup

```
git clone ssh://timotijhof.net/~/git/wp-theme-ttnet.git
```

or

```
git remote add web ssh://timotijhof.net/~/git/wp-theme-ttnet.git
```

**Done!** You can now `git push` to deploy any time.
