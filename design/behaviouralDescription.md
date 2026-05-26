webapp is using only php.
webapp does keep everything on disk in sqlite where the sqlite database is outside the http-root.
webapp can run on apache and nginx
webapp uses the same style as greenhouse controller 
configuration file config.php is not shipped. template_config.php is shipped and shall be configured with admin name. empty admin name is not accepted. the admin password is NOT in config.php; it is set via the first-run setup wizard and stored as a hash in the database (see FR-SEC-020). 
the webapp is running on a server that either runs on local lan where http is allowed or on the internet with https is provided by the server. http or https security is lied with the admin not the app.

the configuration of the questions and flow can be modified, (addition, change, removal) from the admin gui and is saved in the webapp.

user gui:
webapp for user adapted for optimal mobile use. 
user login is unlimited using a single cookie, that can be cleared by the user (forget-button) or by the admin.

user accesses web-app first time
system does not recognises. 
offers option to enter name.
stores cookie on phone for future recognition
user is recognised by ID

user accesses after registration
cookie recognised and welcomed by registered name
user can change name, 
user can remove cookie by clicking forget me buttonforget

admin gui: 
webapp for admin is not optimized for mobil use.
admin login is timed-out after the in the config set time. 
separate admin gui which is not mentioned in the user gui. the admin has to know the exact url e.g.: ./management
admin uses administrator password. the password is NOT stored in the config file; it is set via a first-run setup wizard and stored as a salted hash in the database (see functionalRequirements.md FR-SEC-020 and technicalDesignSpecification.md TDS-AUTH-080..090). config.php carries only the admin name, not the password. 

in the admin gui it is possible to do all management on the users; list, view, forget (drop cookie, not user), and the observations they logged: list, view, modify, delete, export as .csv.

