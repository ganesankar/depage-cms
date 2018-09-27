RM = rm -rf
I18N = ~/Dev/depage-cms/www/framework/i18n.sh
JSMIN = ~/Dev/depage-cms/www/framework/JsMin/minimize

.PHONY: all min minjs locale locale-php sass sassc push pushdev pushlive doc clean

all: locale

locale:
	cd www/framework/ ; $(I18N)
	php www/framework/Cms/js/locale.php

tags:  $(wildcard www/framework/**/*.php)
	phpctags -R -C tags-cache

doc:
	cd Docs ; git clone https://github.com/depage/depage-docu.git depage-docu || true
	mkdir -p Docs/html/
	#doxygen Docs/Doxyfile
	doxygen Docs/de/Doxyfile
	cp -r Docs/depage-docu/www/lib Docs/html/de/
	cp -r Docs/de/images/ Docs/html/de/images/

clean:
	$(RM) Docs/depage-docu/ Docs/html/

push: pushlive

pushlive: all
	rsync \
	    -k -r -v -c \
	    --exclude '.DS_Store' \
	    --exclude '.git' \
	    --exclude 'cache/' \
	    www/framework www/conf www/index.php jonas@depage.net:/var/www/depagecms/net.depage.edit/

pushdev: all
	rsync \
	    -k -r -v -c \
	    --exclude '.DS_Store' \
	    --exclude '.git' \
	    --exclude 'cache/' \
	    www/framework www/conf www/index.php jonas@depage.net:/var/www/depagecms/net.depage.editbeta/

pushtwins: all
	rsync \
	    -k -r -v -c \
	    --exclude '.DS_Store' \
	    --exclude '.git' \
	    --exclude 'cache/' \
	    www/framework www/conf www/index.php jonas@twins:/var/www/depagecms/net.depage.edit/
