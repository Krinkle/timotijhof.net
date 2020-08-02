# Copyright (c) 2020 Timo Tijhof
#
# Transform anchor tags with tooltips (see plainwhite.scss).
#
# Example:
#
#  {{ post.content | fix_tooltip }}
#
module Jekyll
	module AnchorTooltipFilter
		def fix_tooltip(input)
			input.gsub /(<a [^>]+? (title="[^>"]+?")>[^<]+?)<\/a>/, '\1<span \2 tabindex="0"></span></a>'
		end
	end
end

Liquid::Template.register_filter(Jekyll::AnchorTooltipFilter)
