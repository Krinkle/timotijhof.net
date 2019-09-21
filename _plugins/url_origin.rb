module Jekyll
	module UrlFilter
		def url_origin(input)
			uri = Addressable::URI.parse(input)
			"#{uri.host}"
		end
	end
end

Liquid::Template.register_filter(Jekyll::UrlFilter)
