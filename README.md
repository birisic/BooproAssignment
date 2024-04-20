<!-- Improved compatibility of back to top link: See: https://github.com/othneildrew/Best-README-Template/pull/73 -->
<a name="readme-top"></a>
<!--
*** Thanks for checking out the Best-README-Template. If you have a suggestion
*** that would make this better, please fork the repo and create a pull request
*** or simply open an issue with the tag "enhancement".
*** Don't forget to give the project a star!
*** Thanks again! Now go create something AMAZING! :D
-->



<!-- PROJECT SHIELDS -->
<!--
*** I'm using markdown "reference style" links for readability.
*** Reference links are enclosed in brackets [ ] instead of parentheses ( ).
*** See the bottom of this document for the declaration of the reference variables
*** for contributors-url, forks-url, etc. This is an optional, concise syntax you may use.
*** https://www.markdownguide.org/basic-syntax/#reference-style-links
-->

[//]: # ([![Contributors][contributors-shield]][contributors-url])

[//]: # ([![Forks][forks-shield]][forks-url])

[//]: # ([![Stargazers][stars-shield]][stars-url])

[//]: # ([![Issues][issues-shield]][issues-url])

[//]: # ([![MIT License][license-shield]][license-url])

[//]: # ([![LinkedIn][linkedin-shield]][linkedin-url])



<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://github.com/birisic/BooproAssignment">
    <img src="public/assets/images/boopro-logo.png" alt="Logo" width="600" height="265"/>
  </a>

<h3 align="center">Word Popularity Score App</h3>

  <p align="center">
    This is an assignment project for the company Boopro. The primary objective here was to make a scalable web application 
in Laravel 11 that would search GitHub issues and efficiently calculate a popularity score for any word. The application 
and the underlying MySQL database are designed to support adding new search providers in the future.
    <br />
    <br />
    <br />
  </p>
</div>



<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li><a href="#how-to-kick-start-the-project">How To Kick-Start The Project</a></li>
    <li><a href="#about-the-project">About The Project</a></li>
    <li><a href="#about-the-database">About The Database</a></li>
  </ol>
</details>



<!-- AFTER CLONING -->
## How To Kick-Start The Project

Here's a list of things you should check before trying to run the app:
<ul>
    <li>clone the repo using git clone</li>
    <li>run composer install to install dependencies</li>
    <li>give a correct name for the database in the ENV file (mine was boopro-assignment)</li>
    <li>run php artisan migrate:fresh —seed to create the DB and seed it with providers</li>
    <li>add an access token in the ENV file under the key GITHUB_PERSONAL_ACCESS_TOKEN</li>
    <li>add an endpoint in the ENV file under the key GITHUB_API_ISSUES_ENDPOINT</li>
    <li>start a MySQL server</li>
    <li>run php artisan serve</li>
    <li>open Postman, enter the route URL and send a GET request</li>
</ul>

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- ABOUT THE PROJECT -->
## About The Project

The project was realised as a `Laravel API` and its main intent was to make a custom way of calculating the popularity score for
a given word. The score is represented by float numbers from 0 to 10 and its calculated 
based on this formula: `(positive_results / total_results) * 10`.
<br/>
<br/>
Positive results represent the total number of occurrences of the `{word} rocks` pattern, where word is the searched word.
Negative results just follow a different pattern, `{word} sucks`. The application is made to use a search provider and its contexts
to look for these positive and negative results and calculate a score based on them. The search provider in this case was GitHub itself
with its `REST API`, and the context was all publicly available GitHub issues, from all public repositories, or from a single repository. 
<br/>
<br/>
The application has one single API `GET route` with two parameters, the first of which is mandatory, and it's the actual word 
that will be searched. The second parameter is an optional platform with a default value of "GitHub" (`/score/{word}/{platform?}`).
<br/>
<br/>
`SearchController` is responsible for handling requests coming in from this route, and it has a couple of fields that are
used throughout the class extensively. It uses `environment variables` which hold a REST API endpoint and a GitHub access token
for authenticating requests sent to the GitHub REST API endpoint from which the app receives its data. The controller also possesses
a private field of type `SearchableInterface`, which is used to store a reference to an object of a service class that implements
the interface, ensuring the `Dependency Inversion` principle from SOLID is kept. The object itself is injected using `Dependency Injection`
from another private controller method into the method which accepts the route parameters, allowing the instantiation of the right
service class based on the route parameter $platform. This allows for a single generic implementation of any service class object
created, provided they all implement the SearchableInterface, and a clean separation of concerns which `decouples the code`.
<br/>
<br/>
`SearchableInterface` is a contract made for any service class created with the purpose of implementing searching and calculating
word popularity score functionalities through `polymorphism`. It `prescribes two methods` which all classes that adhere to this 
interface must provide an implementation for: `search` and `calcPopularityScore`. This interface serves as an `abstraction`
for the controller to interact with various search provider service classes in a generic way, and as an assigner of certain rules
for the same implementing service classes.
<br/>
<br/>
To not allow any platform parameter values which are not supported on the system, and also minimize human error in validation,
the controller uses the `SearchProviderEnum` to get name constants for all the supported search providers and validate
the parameter value based on that.
<br/>
<br/>
The controller also includes the ability to check whether there are any records for the given word parameter already present
in the database, and `if the data is not too stale`. It checks for this by comparing the time of the search with the updated_at column
and checks if there isn't more than an hour of difference between the two. If the check passes, it retrieves the data straight
from the database `without searching and making network calls again`, significantly reducing the time of execution. Otherwise, 
it has no other option but to search again and try to refresh the data. 
<br/>
<br/>
To aid the controller in working with the different search providers, I've envisioned that a different class will exist
for every new search provider supported, `separating concerns` and `decoupling the code` while following the
`Single Responsibility` and `Open-Closed` SOLID Principles. For the purposes of this assignment, I've added a new App\Services layer
and inside of it created three service classes: `AbstractSearchProviderService`, `GitHubService` and `XService`.
<br/>
<br/>
An `abstract class` is provided as the primary base class for all new search provider classes created in the future. 
It includes some general fields which will probably be needed for all extended classes, and `getter methods` for accessing
services' protected fields.
<br/>
<br/>
The `GitHubService class` is the place where all the magic happens. It is responsible for handling `REST API network calls`, 
updating database records, and returning scores or messages to the controller. It contains a robust set of methods for `encapsulating`
and working with the `business logic` of the assignment.
<br/>
<br/>
`Postman` was used heavily for the development purposes of this assignment, and here are two examples of successful executions, 
the first one being with a network call, and the second being loaded from the database:

<img src="public/assets/images/postman-javascript.png" alt="Database" width="900"/>
<img src="public/assets/images/postman-kotlin.png" alt="Database" width="900"/>

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- DATABASE DESIGN -->
## About The Database

This is the physical design of the database in use:

<img src="public/assets/images/database-design.png" alt="Database" width="826"/>

In this image we see the four tables that enable storing and querying search results. The `contexts table` serves to distinguish
between the different possible 'contexts' or 'spaces' our search results could reside in. It's created with the idea of supporting
multiple different providers, and multiple different context repositories within those providers, hence the columns `type, 
owner_username and name`, which are all nullable fields.
<br/>
<br/>
The `searches table` has a composite primary key comprised of four columns: `word_id, context_id, count_pages and items_per_page`.
It consists of four columns because that's where all the data that uniquely defines a 'search' is residing. The results may
vary drastically if we reduce or increase the number of pages or even the number of items per every page received. Word_id and
context_id also have foreign key constraints on them.

<p align="right">(<a href="#readme-top">back to top</a>)</p>
