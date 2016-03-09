ILIAS Skin Transformer plugin
=============================

Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv2, see LICENSE

* Author:   Fred Neumann <fred.neumann@ili.fau.de>

Installation
------------

When you download the Plugin as ZIP file from GitHub, please rename the extracted directory to *SkinTransformer*
(remove the branch suffix, e.g. -master).

1. Copy the SkinTransformer directory to your ILIAS installation at the followin path
(create subdirectories, if neccessary): Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
2. Go to Administration > Plugins
3. Choose action  "Update" for the SkinTransformer plugin
4. Choose action  "Activate" for the SkinTransformer plugin

Usage
-----
This plugin for the LMS ILIAS open source allows to adapt the user interface with XSL transformations 
being applied to the html code before delivery.

* To adapt the basic skin of ILIAS, copy the examples files to templates/default directory of your installation
* To adapt a specific skin, copy the examples files to skin directory under Customizing/global
* Add your own xsl files for specific templates or ILIAS modules to the same directory 
* Create <trans> elements in skin_transformations.xml to activate their processing

See [examples/skin_transformations.xml](examples/skin_transformations.xml) for details.

