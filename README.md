# timotijhof.net

## Usage

Create the Bundler environment (e.g. Jekyll and plugins):

<pre lang="sh">
bundle install
</pre>

Upgrade packages based on the current [Gemfile](./Gemfile):

<pre lang="sh">
bundle update
</pre>

-------

Start a local server:

<pre lang="sh">
bundle exec jekyll serve
</pre>

This starts a Jekyll server listening at <http://localhost:4000>.

-------

To start a local server that can be accessed by other devices on the network:

<pre lang="sh">
# For example, if 192.168.1.101 is your LAN-IP address.
bundle exec jekyll serve --trace -H 192.168.1.101
</pre>

This starts a Jekyll server listening at <http://192.168.1.101:4000>.

-------

Generate the site (without starting a server)

<pre lang="sh">
bundle exec jekyll build
</pre>

## Deployment

### Remote setup

Inspired by <http://toroid.org/git-website-howto>.

Create Git repository:

```
cd ~/git/
mkdir timotijhof-blog.git && cd timotijhof-blog.git
git init --bare
# Enable symlinks (used by _plugins/symlink for subdomain docroots)
git config core.symlinks true
```

Create plain directory to be the recipient of deployments:

```
cd ~/git-deploy/
mkdir timotijhof-blog
```

Set up a `post-receive` hook in the Gi repo, which will update the
live checkout upon remote push. Make sure to mark it as executable.

`cat ~/git/timotijhof-blog/hooks/post-receive`
```
#!/bin/sh
GIT_WORK_TREE=/home/deb35044/git-deploy/timotijhof-blog/ git checkout -f
```

### Local setup

After generating the site at least once, connect the git-ignored `_site`
directory as clone of this remote repository.

```
cd _site
git init
git remote add web ssh://timotijhof.net/~/git/timotijhof-blog.git
```

## Credits

* The Jekyll theme is based on [_plainwhite_](https://github.com/thelehhman/plainwhite-jekyll)
by Samarjeet Singh.

* The tag index logic is based on [Jekyll Tags](http://longqian.me/2017/02/09/github-jekyll-tag/) by Long Qian.
