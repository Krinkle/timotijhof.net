# timotijhof.net

## Local workflow

Create the Bundler environment (e.g. Jekyll and plugins):

```
bundle install
```

Upgrade packages based on the current [Gemfile](./Gemfile):

```
bundle update
```

-------

Start a local server:

```
bundle exec jekyll serve
```

This starts a Jekyll server listening at <http://localhost:4000>.

-------

Or, to start a local server such that it allows requests from other devices on the network:

```
# For example, if 192.168.1.101 is your LAN-IP address.
bundle exec jekyll serve -H 192.168.1.101 --trace
```

This starts a Jekyll server listening at <http://192.168.1.101:4000>.

## Deployment

**Step 1**: Generate the site (without starting a server).

```
bundle exec jekyll build
```

**Step 2**: Commit.

```
cd _site/
_site$ git add -p
_site$ git commit
```

**Step 3**: Push.

```
_site$ git push
```

**Done!** The change is now deployed.

## One-time setup

### Remote setup

_Inspired by <http://toroid.org/git-website-howto>._

**Step 1**: Create Git repository, on your server:

```
cd ~/git/
mkdir timotijhof-blog.git && cd timotijhof-blog.git
git init --bare
# Enable symlinks (I use this in _plugins/symlink for my subdomains)
git config core.symlinks true
```

**Step 2**: Create a plain directory, on your server, to be the recipient of deployments:

```
cd ~/git-deploy/
mkdir timotijhof-blog
```

**Step 3**: Create a `post-receive` hook in the Git repository. This hook is a shell script that the server will execute after you push a Git commit to it. The script will make sure your deployment directory will become a checkout of the latest commit.

Remember to mark the `post-receive` file as executable.

```
~/git/timotijhof-blog/$ cat hooks/post-receive
```
```
#!/bin/sh
export GIT_WORK_TREE=/home/deb35044/git-deploy/timotijhof-blog/
git checkout -f
```
```
~/git/timotijhof-blog$ chmod +x hooks/post-receive
```

**Done!** After the local setup is done (see below), you can push to this repository, and a static copy of it will be maintained automatically in the deploy directory. I use this method (instead of simply exposing the Git repository from my web server) so that `.git` is naturally not exposed, and so that there is no Git "working copy" to worry about, which could sometimes get dirty or stuck for some reason.

You would then e.g. replace `~/public_html` with a symlink to `~/git-deploy/timotijhof-blog/`, or if you can configure your web server directly, set DocumentRoot to this directory.

### Local setup

**Step 1**: Have the static site source code locally. E.g. a clone of this repository if you've got one already, or just a local directory to work in.

**Step 2**: Generate the site locally at least once. For Jekyll, this produces a `_site/` directory. If you use Git for the source code as well, you may want to `.gitignore` that.

**Step 3**: Connect your `_site/` directory to your server's Git repostory.

```
cd _site/
_site/$ git init
_site/$ git remote add web ssh://timotijhof.net/~/git/timotijhof-blog.git
```
**Done!** You can now `git add` your generated site, and commit/push them to your server, at which point they'll instantly appear on your domain.

## Credits

* The Jekyll theme is based on [_plainwhite_](https://github.com/thelehhman/plainwhite-jekyll)
by Samarjeet Singh.

* The tag index logic is based on [Jekyll Tags](http://longqian.me/2017/02/09/github-jekyll-tag/) by Long Qian.
