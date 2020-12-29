# The 'file_version_query' Liquid filter appends a query parameter with a file hash.
#
# See also /assets/.htaccess
#
# Example: Basic
#
# ```
# {{ "/assets/foo.png" | file_version_query }}
# /assets/foo.png?v=01234567
# ```
#
# Example: Override which files are hashed, e.g. for generated assets that don't exist
# at the time of access.
#
# ```
# {{ "/assets/style.css" | file_version_query: "/_sass/foo.scss", "/_sass/bar.scss" }}
# /assets/style.css?v=01234567
# ```
#
# License: 0BSD
#
# -------
#
# Copyright 2020 Timo Tijhof <https://timotijhof.net>
#
# This is free and unencumbered software released into the public domain.
#
# Permission to use, copy, modify, and/or distribute this software for any
# purpose with or without fee is hereby granted.
#
# THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
# REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
# AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
# INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
# LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
# OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
# PERFORMANCE OF THIS SOFTWARE.
#
require 'digest'

module Jekyll
	module FileFilter
		def file_version_query(input, *filenames)
			filenames = [input] unless filenames.length > 0
			hexes = filenames.map do |filename|
				digest = Digest::MD5.file File.join(__dir__, '..', filename)
				digest.hexdigest
			end
			hex = hexes.length > 1 ? Digest::MD5.hexdigest(hexes.join(' ')) : hexes[0]
			"#{input}?v=#{hex[0..7]}"
		end
	end
end

Liquid::Template.register_filter(Jekyll::FileFilter)
