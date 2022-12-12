# An SDK to easily work with the Mailcoach API in Laravel apps

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-mailcoach-sdk.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-mailcoach-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-mailcoach-sdk/run-tests?label=tests)](https://github.com/spatie/laravel-mailcoach-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-mailcoach-sdk/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/spatie/laravel-mailcoach-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-mailcoach-sdk.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-mailcoach-sdk)

This package contains the PHP SDK to work with [Mailcoach](https://mailcoach.app). Both self-hosted (v6 and up) and hosted Mailcoach (aka Mailcoach Cloud) are supported. Using this package you can manage email lists, subscribers and campaigns.

Here are a few examples:

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

// creating a campaign
$campaign = Mailcoach::createCampaign([
    'email_list_uuid' => 'use-a-real-email-list-uuid-here',
    'name' => 'My new campaign',
    'fields' => [
        'title' => 'The title on top of the newsletter',
        'content' => '# Welcome to my newsletter',
    ],
]);

// sending a test of the campaign to the given email address
$campaign->sendTest('john@example.com');

// sending a campaign
$campaign->send();
```

By default, Mailcoach' endpoints will are paginated with a limit of 1000. The package makes it easy to work with paginated resources. Just call `->next()` to get the next page.

```php
// listing all subscribers of a list
$subscribers = $mailcoach->emailList('use-a-real-email-list-uuid-here')->subscribers();

do {
    foreach($subscribers as $subscriber) {
        echo $subscriber->email;
    }
} while($subscribers = $subscribers->next())
```

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-mailcoach-sdk.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-mailcoach-sdk)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-mailcoach-sdk
```

You must publish the config file with:

```bash
php artisan vendor:publish --tag="mailcoach-sdk-config"
```

This is the contents of the published config file:

```php
return [
    /*
     *  You'll find both the API token and endpoint on Mailcoach'
     *  API tokens screen in the Mailcoach settings.
     */
    'api_token' => env('MAILCOACH_API_TOKEN'),

    'endpoint' => env('MAILCOACH_API_ENDPOINT'),
];
```

In your `.env` file you should add the entries from the config file mentioned above. You'll find both the API token and endpoint on Mailcoach' API tokens screen in the Mailcoach settings.

## Usage

You can use the `Spatie\MailcoachSdk\Facades\Mailcoach` facade to perform most operations.

### Handling pagination

There are several methods, such as `emailLists()`, 'subscribers()' and `campaigns()` to will return paginated results. To get the next page of results just call `next()` on a result. If there are no more results, that method returns `null`.

Here's how you display the email addresses of every subscriber on a list

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$subscribers = Mailcoach::subscribers('<email-list-uuid');

do {
    foreach($subscribers as $subscriber) {
        echo $subscriber->email;
    }
} while($subscribers = $subscribers->next())
```

On paginated results, `$subscribers` in the example above there are also some more convenience methods:

- `results()`: get the results. A results object is also iterable, so you can also get to the results by simply using the object in a loop
- `next()`: fetch the next page of results
- `previous()`: fetch the previous page of results
- `currentPage()`: get the current page number
- `total()`: get the total number of results across all pages
- `nextUrl()`: get the URL that will be called to get the next page of results
- `previousUrl()`: get the URL that will be called to get the previous page of results

### Working with email lists

Here's how to get all email lists:

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$emailLists = Mailcoach::emailLists();
```

You can get a single email list:

```php
$emailList = $this->mailcoach->emailList('<uuid-of-email-list>');
```

This is how you can create an email list:

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

Mailcoach::createEmailList(['name' => 'My new email list']);
```

You can get properties of email list:

```php
$emailList->name;
$emailList->uuid;
// ...
```

Take a look at the source code of `Spatie\MailcoachSdk\Resources\EmailList` to see the list of available properties.

You can update an email list by change one of the properties and calling `save()`.

```php
$emailList->name = 'Updated name';
$emailList->save();
```

You can delete an email list by calling `delete()`.

```php
$emailList->delete();
```

### Working with subscribers

To get all subscribers of a list, you can call `subscribers()` on an email list.

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$subscribers = Mailcoach::emailList('<uuid-of-email-list>')->subscribers();
```

Optionally, you can pass filters to `subscribers()`. Here how to get all subscribers with a Gmail-address.

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$subscribers = Mailcoach::emailList('<uuid-of-email-list>')
   ->subscribers(['filter[email]=gmail.com']);
```

Alternatively, you can call `subscribers()` on  `$mailcoach`

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$subscribers = Mailcoach::subscribers('<uuid-of-email-list>', $optionalFilters);
```

There's also a convenience method to quickly get a subscriber from a list.

```php
// returns instance of Spatie\MailcoachSdk\Resources\Subscriber
// or null if the subscriber does not exist.

$subscriber = $emaillist->subscriber('john@example.com');
```
Alternatively, you can get a subscriber by its UUID:

```php
$subscriber = $mailcoach->subscriber('<subscriber-uuid>');
```

This how you can create a subscriber:

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$subscriber = Mailcoach::createSubscriber(
    emailListUuid: '<email-list-uuid>',
    attributes: [
        'email' => '<email-address>',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
```

You can get properties of a subscriber:

```php
$subscriber->firstName;
$subscriber->email;
// ...
```

Take a look at the source code of `Spatie\MailcoachSdk\Resources\Subscriber` to see the list of available properties.

You can update a subscriber by change one of the properties and calling `save()`.

```php
$subscriber->firstName = 'Updated name';
$subscriber->save();
```

You can confirm, unsubscribe and delete a subscriber by calling these methods.

```php
$subscriber->confirm();
$subscriber->unsubscribe();
$subscriber->delete();
```

### Working with campaigns

Here's how to get all campaigns.

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$campaigns = Mailcoach::campaigns();
```

You can also get a single campaign();

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$campaign = Mailcoach::campaign('<campaign-uuid>');
```

This is how you can create a campaign:

```php
use Spatie\MailcoachSdk\Facades\Mailcoach;

$campaign = Mailcoach::createCampaign([
   'name' => 'My new campaign',
   'subject' => 'Here is some fantastic content for you',
   'email_list_uuid' => '<email-list-uuid>',
   
   // optionally, you can specify the uuid of a template
   'template_uuid' => '<template-uuid>',
   
   // if that template has field, you can pass the values
   // in the `fields` array. If you use the markdown editor,
   // we'll automatically handle any passed markdown
   'fields' => [
        'title' => 'Content for the title place holder',
        'content' => '# My title',
    ],    
]);
```

You can get properties of a campaign:

```php
$campaign->name;
$campaign->subject;
// ...
```

Take a look at the source code of `Spatie\MailcoachSdk\Resources\Campaign` to see the list of available properties.

You can update a campaign by change one of the properties and calling `save()`.

```php
$campaign->name = 'Campaign';
$campaign->save();
```

A test mail will be sent when calling `sendTest()`:

```php
// sending a test to a single person
$campaign->sendTest('john@example.com');

// sending a test to multiple persons
$campaign->sendTest(['john@example.com', 'jane@example.com']);
```

The campaign will be sent to all subscribers of your list, by calling `send()`:

```php
$campaign->send();
```

A campaign can be deleted:

```php
$campaign->delete();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
