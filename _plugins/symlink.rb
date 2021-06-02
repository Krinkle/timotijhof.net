# Copyright (c) 2019 Timo Tijhof
#
# Creates one or more symlinks in the _site output.
#
# This is for compatibility with my web host where sub domains
# are required to be served from `docroot/:sub`, whereas I want
# `docroot` to be the document root fo the main domain only.
#
# It supports htaccess, but htaccess can't rewrite to outside
# the current directory.
#
# It supports symlinks, but Jekyll refuses to copy a (locally dead-end)
# symlink from source to destination. Hence this plugin.
module Jekyll
	Jekyll::Hooks.register :site, :post_write do |site|

		# Symlink for rocvt.timotijhof.net
		File.symlink("/home/deb35044/domains/timotijhof.net/rocvt-sub-public_html/", "#{site.dest}/rocvt")

		# Symlink for stats.timotijhof.net
		File.symlink("/home/deb35044/domains/timotijhof-stats.localhost/private_html/", "#{site.dest}/stats")

		# Symlink for treasure21.timotijhof.net
		File.symlink("/home/deb35044/domains/timotijhof.net/treasure21-sub-public_html/", "#{site.dest}/treasure21")

	end
end
