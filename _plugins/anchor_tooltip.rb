# Copyright (c) 2020 Timo Tijhof
#
# Transform anchor tags with tooltips (see plainwhite.scss).
#
# If used in cunjuction with ExternalLinkFilter, then AnchorTooltipFilter
# must be applied first.
#
#
# Example:
#
#  {{ post.content | fix_tooltip }}
#
#  {{ post.content | fix_tooltip | fix_extlink }}
#
module Jekyll
	module AnchorTooltipFilter
		def fix_tooltip(input)
			input.gsub /(<a href="[^#][^>"]+" (title="[^>"]+?")>[^<]+?)<\/a>/, '\1<span \2 tabindex="0"></span></a>'
		end
	end
end

Liquid::Template.register_filter(Jekyll::AnchorTooltipFilter)
