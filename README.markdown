# A Collection of CakeEmail transport classes #
## Currently supported services ##
 * Postmark (http://postmarkapp.com)
 * Amazon SES

## Postmark ##

### Configuration ###

Add the following configuration in your Config/email.php file:

```php
<?php
  public $postmark = array(
    'transport' => 'CakeEmailTransports.Postmark',
    'secure' => false, //Set to true to use https
    'apiKey' => '__your_api_key__', //Your postmark API key
    'debug' => false //Set to true to test your configuration without sending the email
    'tag' => false //Optionally set a tag that always has to be sent with every email
  );
```
### Usage ###

The following example shows you how to send an email with the postmark transport:

```php
<?php
$email = new CakeEmail('postmark');

$email->to('receiver@example.com');
$email->from('sender@example.com')
$email->subject('Hello World');

$email->send('This is an example email');
```
You can also set the postmark tag property on an email by email basis:
```php
<?php
$email->addHeaders(array('X-Tag' => 'my-tag'));
```

## Amazon SES ##

### Requirements ###

 * Amazon Web Services SDK (http://pear.amazonwebservices.com/)

### Configuration ###

Add the following configuration in your Config/email.php file:

```php
<?php
  public $amazon = array(
    'transport' => 'CakeEmailTransports.Amazon',
    'key' => '__your_amazon_access_key',
    'secret' => '__your_amazon_secret_access_key',
  );
```
### Usage ###

The following example shows you how to send an email with the postmark transport:

```php
<?php
$email = new CakeEmail('amazon');

$email->to('receiver@example.com');
$email->from('sender@example.com')
$email->subject('Hello World');

$email->send('This is an example email');
```

# License #
Copyright © 2012 Jelle Henkens

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the “Software”), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.