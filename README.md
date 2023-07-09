# Currency Converter

A Service to convert currencies in each other.

##Initialization

* Copy this repository on your local computer:

`git clone git@github.com:ashandi/rate_converter.git`

* Run command below to set up the Service and database (from the project directory):

`cd rate_converter && make init`

* Load currency rates by using this command:

`docker-compose exec app php bin/console app:load-rates rates_source.ecb rates_source.coin_desk`

##Usage

open `localhost:8080` and use the web page for currency conversion.

##Tests

use command `docker-compose exec app make test` to run Service tests.

##Add new Rates Source

1. Create new class in `src/Service/RatesSources` and implement interface `RatesSource`.
2. Add your class to `config/services.yaml`, set it's alias (e.g. `rates_source.my_new_source`) and make it public.
3. Provide alias of your class in arguments to command 'app:load-rates'