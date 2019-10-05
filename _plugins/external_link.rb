# Copyright (c) 2019 Timo Tijhof
#
# Add additional attributes to external links, such as to open them
# in a new window.
#
# Example:
#
#  {{ post.content | fix_extlink }}
#
module Jekyll
	module ExternalLinkFilter
		def fix_extlink(input)
			input.gsub '<a href="http', '<a target="_blank" rel="noopener" href="http'
		end
	end
end

Liquid::Template.register_filter(Jekyll::ExternalLinkFilter)
