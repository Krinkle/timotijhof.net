# Copyright (c) 2019 Timo Tijhof
#
# Adds a 'url_origin' filter for use in Liquid templates,
# which extras the domain name of a url.
#
# Input:
#
#  {{ "https://example.org/foo/?q=bar" | url_origin }}
#
# Output:
#
#  example.org
#
module Jekyll
	module UrlFilter
		def url_origin(input)
			uri = Addressable::URI.parse(input)
			"#{uri.host}"
		end
	end
end

Liquid::Template.register_filter(Jekyll::UrlFilter)
