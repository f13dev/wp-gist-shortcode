# Gist shortcode
Embed information about a GitHub Gist, including the code of each file, into a WordPress page or blog post using shortcode.

# Plugin Details
Website: http://f13dev.com/wordpress-plugin-gist-shortcode/
Tags: github, gist, shortcode, embed, code
Requires WP Version: 3.0.1
Tested up to WP Version: 4.5.3
Stable tag: 1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

# Description
If you are a programmer who uses GitHubs Gist to store code snippets, why not share your code on your WordPress powered website using shortcode.

Simply install Gist Shortcode by F13Dev, add a GitHub API Key (optional), then embed gists using shortcode.

Features include:

* Cached using Transient
* Clean styled appearance
* Shows the creation date and last edited date
* Shows the Gist description if one is set
* Displays the filename, file size, download link and full code for each file in the gist
* Code is displayed in using Googles PrettyPrint in a fixed height scrollable container
* Works without an API Key, but also has the option to add a Key should you require a higher rate limit

# Installation
1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Optionally add a GitHub API key via the admin panel, Settings -> F13 Gist Shortcode
4. Add the shortcode [gist gid="A Gist ID"] to the desired location on your blog

# FAQ
Q) Is a GitHub API token required

A) No... Although it is recommended, especially if you wish to have the cache timeout set to a low value, or if you require regular access to the GitHub API through other areas of your website.

# Screenshot
![An example showing the Gist Shortcode  in use.](/screenshot-1.png?raw=true "Gist Shortcode")

1. An example showing the Gist Shortcode in use.

# Changelog
1.0
* Initial release

# Upgrade notice
= 1.0 =
* Initial release
