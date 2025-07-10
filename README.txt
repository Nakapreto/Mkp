=== MKP Super Links ===
Contributors: mkpdigital
Tags: multisite, super admin, toolbar, navigation, links, admin, network
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Network: true

Advanced multisite super links management for WordPress networks. Adds enhanced toolbar links, site navigation, and admin tools for super administrators.

== Description ==

**MKP Super Links** is a powerful WordPress plugin designed specifically for WordPress Multisite networks and super administrators. It enhances the WordPress admin toolbar with quick access links, improves site navigation, and provides advanced tools for managing large WordPress networks efficiently.

= Key Features =

* **Enhanced Toolbar Navigation** - Quick access to all network sites and admin areas
* **Site Management Tools** - Streamlined interface for managing multiple sites
* **Network Admin Enhancements** - Additional tools for super administrators
* **Custom Link Management** - Create and organize custom navigation links
* **Performance Optimization** - Cached site data for improved performance
* **Responsive Design** - Works perfectly on desktop and mobile devices

= Perfect For =

* WordPress Multisite Network Administrators
* Super Administrators managing multiple sites
* Web agencies managing client sites
* Organizations with multiple WordPress installations
* Anyone who needs quick access to multisite admin tools

= Main Benefits =

* **Save Time** - Quick access to frequently used admin areas
* **Improve Workflow** - Streamlined navigation between sites
* **Enhanced Productivity** - Fewer clicks to get to important pages
* **Better Organization** - Organized links and site overview
* **Performance Focused** - Optimized for large networks

= Network Features =

* Network-wide plugin activation and management
* Sites overview with detailed information
* Quick site creation and management tools
* User management across the network
* Network settings and configuration
* Bulk operations for multiple sites

= Site-Specific Features =

* Quick access to dashboard, posts, pages, and media
* Direct links to site settings and customization
* User management for individual sites
* Plugin and theme management per site
* Comments and content moderation tools

= Advanced Tools =

* Cache management for improved performance
* Settings export/import for easy migration
* Custom CSS for personalized styling
* Feature toggles for customized experience
* Debug and logging capabilities

= Compatibility =

* WordPress 5.0+ (tested up to 6.8)
* PHP 7.4+
* Works with or without Multisite
* Compatible with all major themes and plugins
* Responsive design for all devices

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "MKP Super Links"
4. Click "Install Now" and then "Activate"
5. For Multisite: Network activate the plugin

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin (Network activate for Multisite)

= Network Installation =

1. Upload the plugin to your WordPress installation
2. Go to Network Admin > Plugins
3. Network activate "MKP Super Links"
4. Configure settings in Network Admin > Super Links

== Frequently Asked Questions ==

= Does this plugin work with regular WordPress sites? =

Yes! While designed primarily for Multisite networks, MKP Super Links works perfectly with regular WordPress installations, providing enhanced admin navigation and tools.

= Is this plugin compatible with other admin enhancement plugins? =

Yes, MKP Super Links is designed to work alongside other WordPress plugins. It adds its own toolbar items without interfering with existing functionality.

= Can I customize the appearance of the toolbar items? =

Absolutely! The plugin includes options for custom CSS, and you can style the toolbar items to match your branding or preferences.

= Does the plugin affect site performance? =

MKP Super Links is optimized for performance with built-in caching mechanisms. It actually improves admin performance by providing quicker access to common tasks.

= Can I export and import plugin settings? =

Yes, the plugin includes export/import functionality for easy migration between sites or backup purposes.

= Is the plugin translation ready? =

Yes, MKP Super Links is fully internationalized and ready for translation into any language.

= What permissions are required to use the plugin? =

Different features require different permissions:
- Super Admins: Full access to all features
- Administrators: Access to site-specific features
- Editors: Limited access based on settings

= Can I disable specific features? =

Yes, the plugin includes feature toggles so you can enable or disable specific functionality based on your needs.

= Does the plugin work with custom post types? =

Yes, the plugin automatically detects and provides access to custom post types and their admin pages.

= Is there a way to clear the plugin cache? =

Yes, there's a "Clear Cache" button in the plugin tools section, and cache can also be cleared programmatically.

== Screenshots ==

1. Enhanced toolbar with Super Links menu
2. Network admin dashboard with quick actions
3. Sites overview with detailed information
4. Plugin settings and configuration options
5. Tools page with cache management and export/import
6. Mobile-responsive design on tablet and phone

== Changelog ==

= 1.0.0 - 2025-01-10 =
* Initial release
* Enhanced toolbar navigation for Multisite networks
* Site management and overview tools
* Network admin enhancements
* Performance optimization with caching
* Settings export/import functionality
* Custom CSS support
* Full internationalization support
* Responsive design implementation
* Comprehensive admin interface
* Security hardening and validation

== Upgrade Notice ==

= 1.0.0 =
Initial release of MKP Super Links. Install now to enhance your WordPress Multisite admin experience!

== Advanced Configuration ==

= Custom CSS =

You can add custom CSS to style the plugin elements:

```css
#wp-admin-bar-mkp-super-links .ab-item {
    color: #your-color !important;
}
```

= Filters and Hooks =

The plugin provides several filters for customization:

* `mkp_quick_action_links` - Modify quick action links
* `mkp_site_info` - Filter site information
* `mkp_sanitize_options` - Custom option sanitization

= Performance Optimization =

For large networks, consider:

* Enabling caching (enabled by default)
* Adjusting cache duration based on your needs
* Using the network-wide activation for better performance

= Security =

The plugin follows WordPress security best practices:

* Nonce verification for all actions
* Capability checks for all features
* Data sanitization and validation
* Secure AJAX implementations

== Support ==

For support, documentation, and updates, visit:

* Plugin Homepage: https://mkpdigital.com/plugins/mkp-super-links
* Documentation: https://docs.mkpdigital.com/mkp-super-links
* Support Forum: WordPress.org support forums
* GitHub Repository: https://github.com/mkpdigital/mkp-super-links

== Privacy Policy ==

MKP Super Links does not collect, store, or transmit any personal data. All settings and data remain on your WordPress installation.

== Credits ==

Developed by MKP Digital with ❤️ for the WordPress community.

Special thanks to all the beta testers and contributors who helped make this plugin better.

== Donations ==

If you find this plugin useful, please consider supporting its development:

* PayPal: https://paypal.me/mkpdigital
* Ko-fi: https://ko-fi.com/mkpdigital
* GitHub Sponsors: https://github.com/sponsors/mkpdigital