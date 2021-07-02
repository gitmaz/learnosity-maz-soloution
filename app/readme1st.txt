@@@ initial setup @@@
I have used latest symfony skeleton application to handle the required api.
I am posting both docker version (not tested) and stand alone application (to be served on an standalone web server)

1- cd to root folder for the application

2- open a console terminal and run:
   php composer.phar install

3- create an empty mysql database called codingchallenge (if not created- for standalone non docker version I have used
      user root with no password, but docker one uses same as docker mysql server configs)

      mysql -u root
      create database codingchallenge
      quit

4- run console command to create required schema (for tables question_meta and question_stat)
   php bin/console doctrine:schema:create

   (if any change is made to classes on Entity folder do this one instead)
   php bin/console doctrine:schema:update --force

@@@ For testing the api endpoints in the browser @@@
5-serve the public folder as document root or use docker
  (my solution uses the latest symfony which needs php 7. I have copied the symfony files on docker image app folder and
   html folder but I did not get time to run the application on Docker)
  But the application is tested using my xampp that runs apache and php 7. Below is setup guide on apache

  example server configuration for apache: On windows you can add this configuration in localhost server conf/extra/httpd-vhosts.conf

 <VirtualHost *:80>
  DocumentRoot "C:\worklab/questat/public"
  ServerName questat.local
  <Directory "C:/worklab/questat">
         Options Indexes FollowSymLinks MultiViews
         AllowOverride all
         Order Deny,Allow
         Allow from all
         Require all granted
  </Directory>
 </VirtualHost>

 and add 127.0.0.1 questat.local
 to your hosts file.

 restart web server.

6-go to web browser and enter this url in address bar to find out more about how to use endpoints:

http://questat.local/?action=help
or use below guide:

  Receive A New Question Meta:
    http://questat.local/?action=receive&activity_id=1&user_id=1&timestamp=1&name=start
    http://questat.local/?action=receive&activity_id=1&user_id=1&timestamp=3&name=next
    http://questat.local/?action=receive&activity_id=1&user_id=1&timestamp=5&name=stop

  List Activity Details:
    http://questat.local/?action=lad&activity_id=1

  Find Longest Activity:
    http://questat.local/?action=fla

  Dump All question on specific activity of specific user:
    http://questat.local/?action=dump&activity_id=1&user_id=1

  testing the payload version is done through testCanReceivePayloadAsOfManyEventsViaPost method

@@@ unit and integration testing @@@
7- run below when web server and database schema is ready

 php ./vendor/bin/phpunit
 class tests/Controller/QuestionMetaControllerTest runs as integration test
  Did not get time to right more unit tests on for example Entities.
  last test does not pass but the database shows the function works fine (timing issue?!).

@@@ Notes @@

 The classes can be more elaborated by moving the database stuff to their corresponding
 Repository classes but left as is for better readability and saving time.

 Also handling network problems that can cause disorder on arrival of timestamps is handled
 briefly. Can be more elaborated. For example a maximum delay can be checked and if passed questions can get closed
 at that treshold(and new ones inserted etc).

 The best solution to keep questions cohesive with user data is to change the input to contain both stat and end timestamps.
 This way we qurantee timestamps are place to their corresponding questions even with network problems.
-
 Error handling or Exceptions are not coded due to lack of time.
