# LinguaLeoClient
A library for access to **LinguaLeo.com** content by API from Android-app.
It can:
- auth by user
- getting user's dictionary
- getting collections of materials
- getting materials from collection
- getting translation for any word or phrase from lingualeo base

## Installation
```
composer require jizuscreed/lingualeoclient
```
## Quickstart
Authorization:
```
$linguaLeoClient = new LinguaLeoClient\Client($userEmail, $userPassword);
```
Getting user profile data:
```
$linguaLeoClient->user;
```
Getting user's dictionary:
```
$dictionary = $linguaLeoClient->getDictionary($startLimit, $chunkLimit, $onlyWords);
```
Getting materials collections:
```
$collections = $linguaLeoClient->getCollections();
```
Getting materials from collection (grabs list of materials with its datas and preview):
```
$materials = $linguaLeoClient->getMaterialsFromCollection(Collection $collection, $chunkOffset, $chunkLimit);
```
Getting material full text:
```
$materials[0]->getFullText();
```
Getting word's translations:
```
$linguaLeoClient->getWordTranslations('attraction');
```