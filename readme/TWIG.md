# Twig Functions and Filters

EMMWeb contains several Twig functions and filters that can help you building your own templates, making SEO and advertising links unique.
Your template can also contains Twig functions and filters which can be also used, see your template documentation.

[Twig built-in functions or filters](https://twig.symfony.com/doc/2.x/) are also available, for example [url_encode](https://twig.symfony.com/doc/2.x/filters/url_encode.html).

## Documentation

### Functions

#### derefererUrl(url)

Masks referer from url address where user is going to be redirected.
Returns string.
```
{{ derefererUrl('https://www.google.com/') }}
//https://dereferer.me/?https%3A%2F%2Fwww.google.com%2F
```

#### trimOnWord(limit, text)

Cuts long texts after whole word. HTML is cut out from text during the process. Works great for shortening meta description. 
Returns string.
```
{{ trimOnWord('10', 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery.') }}
//A former Roman General sets out to exact vengeance against
```
#### trimOnChar(limit, text)

Cuts long texts on specific character. HTML is cut out from text during the process. Works great for shortening meta description.
Returns string.
```
{{ trimOnChar('48', 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery.') }}
//A former Roman General sets out to exact vengean
```
#### arraySlice(limit, array, key = false)

Slice array to get first "limit" elements. Works with simple arrays without providing specific key or multidimensional arrays where the key has to be provided.
Returns simple array.
```
{{ arraySlice('2', ["Morocco", "Italy", "USA", "Serbia"]) }}
//["Morocco", "Italy"]

    
{{ arraySlice('2', [["id" => 1, "name" => "Action"], ["id" => 2, "name" => "Drama"], ["id" => 8, "name" => "Adventure"]], 'name') }}
//["Action", "Drama"]
```
#### renderIfEverythingIsNotEmpty(template, variables)

Renders template but only if every variable in template exists and is not empty.
Similar to PHP [vsprintf](https://www.php.net/manual/en/function.vsprintf.php). Returns string.
```
{{ renderIfEverythingIsNotEmpty('Rated %%s/%%s', [8.2, 10]) }}
//Rated 8.2/10

//Examples where empty string will be returned
{{ renderIfEverythingIsNotEmpty('Rated %%s/%%s', [0, 10]) }}
{{ renderIfEverythingIsNotEmpty('Rated %%s/%%s', ['', 10]) }}
```

### Filters
#### delimiter

Appends delimiter after variable/block if it is not empty. Returns string.
```
{{ 'Gladiator'|delimiter }}
//Gladiator.
```
#### comma

Join array elements with a comma and space. Works same as Twig [join](https://twig.symfony.com/doc/2.x/filters/join.html) with ', ' as separator. Returns string.
```
{{ ["Action", "Drama"]|comma }}
//Action, Drama
```

## Examples
See examples how it could look like when you use functions and filters in Twig template together with variables.
```
&#127902; Watch/download {{ item.name }} {{ renderIfEverythingIsNotEmpty('&#11088;%%s/%%s', [item.rating.value, item.rating.scale])|delimiter }} {{ arraySlice('3', item.genres, 'name')|comma|delimiter }} {{ trimOnWord('24', item.description) }}
&#128214; Read/download {{ item.name|delimiter }} {{ arraySlice('2', item.persons, 'name')|comma|delimiter }} {{ trimOnChar('66', item.description)|delimiter }}
HD Stream &#128250; {{ item.name|delimiter }} {{ item.releasedYear }}
Download {{ item.name|delimiter }} {{ arraySlice('1', item.persons, 'name')|comma|delimiter }} {{ item.releasedYear }}
https://google.com?q={{ ('Download ' ~ item.name)|url_encode }}
```