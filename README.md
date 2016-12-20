# Alexa Plaxitude Bot

> Alexa, ask Plaxitudes to send [NAME] a [CATEGORY OF PLAXITUDE].

* NAME: Members of our Slack team.
* CATEGORY OF PLAXITUDE:
  * Sports
  * Early Years
  * "Jokes"
  * Kids

----

## Public API

### GET /

API Summary.

Response (JSON string):

* The API methods.

### GET /send

Expected parameters:

* `name` (string) - The name of the person to send a plaxitude.
  * Magic value ("everybody") will send one to everybody.
* `category` (string) - One of the following (case insensitive, without quotes):
  * `"SPORTS"`
  * `"EARLY YEARS"`
  * `"JOKES"`
  * `"KIDS"`
  * (If not specified, it will be chosen at random.)

Response (JSON string):

* `ok` (boolean) - TRUE unless an error occurs
* `error` (string) - Empty unless an error occurs
