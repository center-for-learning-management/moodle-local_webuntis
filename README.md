# moodle-local_webuntis

## About this plugin
This plugin allows an integration of Moodle with WebUntis. The core features are:
- User mapping
- User creation based on WebUntis profile data *)
- Mapping of courses for particular links inside WebUntis (main menu and lesson pages)
- For admins: List of user mappings

## Using this plugin
To use this plugin, you need at least on WebUntis instance and a running Moodle site.

At first, login to your WebUntis instance as Administrator and add the Moodle "platform" (e.g. Eduvidual). In the background, WebUntis tells Moodle the oAuth consumer credentials**). Follow the required steps to add the platform to the various navigation nodes in WebUntis for each user role.

When you're done, links to your Moodle site are shown in the main menu of WebUntis and on each lesson page. When a user clicks on such link for the first time, a mapping of user accounts is required (also for the WebUntis-Administrator!)

### User mapping
Users can now choose to map an existing Moodle account, or create a new one*). Once this user map has been created, the user is automatically logged in each time. This user mapping can be disconnected by the user using the Button "Disconnect from webuntis" in the Moodle main menu.
![Map user account](docs/img/01-map-user.png)

### Tenant specific administration
Using the link to the Moodle on the main menu of WebUntis, the WebUntis-Administrator can choose a target course, and open particular settings regarding the behavior of the sync between WebUntis and Moodle.
![Course mapping for main menu link](docs/img/02a-select-target-mainmenu.png)

At the "settings" page the WebUntis-Administrator can configure options regarding the particular Webuntis tenant. The option regarding the creation of user accounts based on WebUntis profile data must be enabled in the Moodle Website-Administration and on this page too for each WebUntis tenant. This ensures that the consent was given by the Moodle Administrator and the WebUntis Administrator as well. The mapping of user roles as shown on the following screenshot only applies to Moodle sites that use the "eduvidual"-Plugin. This plugin allows to manage several organisations within one Moodle site with separated managements. The user role of WebUntis can be mapped to each organization individually. Moodle sites that don't use eduvidual, simply do not show this option.
![Tenant settings](docs/img/02b-settings.png)

Lastly, the WebUntis Administrator can view a list of actual user mappings.
![User mappings](docs/img/02c-user-mappings.png)

Future versions of this plugin may provide an option for user synchronisation, management of the user mapping by the Administrator, or bulk creation of user accounts.

### Lesson specific configuration

The target course of lesson pages can be selected by Teachers and Administrators. The functionality is the same as with the main menu link.
![Course mapping for lessons](docs/img/03-select-target-lesson.png)

------------------------------------
*) Can be disabled in the Moodle Website-Administration, and requires sufficient profile data in WebUntis!
**) Currently this only works the first time the tenant is added.
