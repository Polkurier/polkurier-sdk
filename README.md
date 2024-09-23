SDK API Polkurier.pl
====================

Dokumentacja API do pobrania pod adresem https://www.polkurier.pl/integracje

W katalogu `./examples` znajdują się przykłady użycia SDK.

Token dostępowy od API można wygenerować z zakłdce Panel klienta > Ustawienia > Token API po zalogowaniu na https://www.polkurier.pl


Instalacja
----------

`composer require polkurier/polkurier-sdk`

Uruchomienie serwera testowego
----------
Aby uruchomić serwer testowy, możesz skorzystać z dwóch sposobów:

### 1. Uruchomienie serwera za pomocą Composer
W pliku `composer.json` znajduje się skonfigurowane polecenie `serve`, które uruchomi serwer testowy. Wystarczy uruchomić następującą komendę:

```bash
composer serve
```
### 2 Uruchomienie serwera za pomocą serweru PHP
Uruchom polecenie 
```bash
php -S localhost:9005 -t examples/
```
w dyrektorii projektu