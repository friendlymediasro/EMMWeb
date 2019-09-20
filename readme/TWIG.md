# Twig Functions and Filters

EMMWeb contains several Twig functions and filters that can help you building your own templates, making SEO and advertising links unique.
Your template can also contains Twig functions and filters which can be also used, see your template documentation.

[Twig built-in functions or filters](https://twig.symfony.com/doc/2.x/) are also available, for example [url_encode](https://twig.symfony.com/doc/2.x/filters/url_encode.html).

## Documentation

### Functions

#### derefererUrl(url)

Masks referer from url address where user is going to be redirected.
Returns string.
```twig
{{ derefererUrl('https://www.google.com/') }}
{# https://dereferer.me/?https%3A%2F%2Fwww.google.com%2F #}
```

#### trimOnWord(limit, text)

Cuts long texts after whole word. HTML is cut out from text during the process. Works great for shortening meta description. 
Returns string.
```twig
{{ trimOnWord('10', 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery.') }}
{# A former Roman General sets out to exact vengeance against #}
```
#### trimOnChar(limit, text)

Cuts long texts on specific character. HTML is cut out from text during the process. Works great for shortening meta description.
Returns string.
```twig
{{ trimOnChar('48', 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery.') }}
{# A former Roman General sets out to exact vengean #}
```
#### arraySlice(limit, array, key = false)

Slice array to get first "limit" elements. Works with simple arrays without providing specific key or multidimensional arrays where the key has to be provided.
Returns simple array.
```twig
{{ arraySlice('2', ["Morocco", "Italy", "USA", "Serbia"]) }}
{# ["Morocco", "Italy"] #}
    
{{ arraySlice('2', [["id" => 1, "name" => "Action"], ["id" => 2, "name" => "Drama"], ["id" => 8, "name" => "Adventure"]], 'name') }}
{# ["Action", "Drama"] #}
```
#### renderIfEverythingIsNotEmpty(template, variables)

Renders template but only if every variable in template exists and is not empty.
Similar to PHP [vsprintf](https://www.php.net/manual/en/function.vsprintf.php). Returns string.
```twig
{{ renderIfEverythingIsNotEmpty('Rated %%s/%%s', [8.2, 10]) }}
{# Rated 8.2/10 #}

{# Examples where empty string will be returned #}
{{ renderIfEverythingIsNotEmpty('Rated %%s/%%s', [0, 10]) }}
{{ renderIfEverythingIsNotEmpty('Rated %%s/%%s', ['', 10]) }}
```

### Filters
#### delimiter

Appends delimiter after variable/block if it is not empty. Returns string.
```twig
{{ 'Gladiator'|delimiter }}
{# Gladiator. #}
```
#### comma

Join array elements with a comma and space. Works same as Twig [join](https://twig.symfony.com/doc/2.x/filters/join.html) with ', ' as separator. Returns string.
```twig
{{ ["Action", "Drama"]|comma }}
{# Action, Drama #}
```

## Examples
See examples how it could look like when you use functions and filters in Twig template together with variables.

To evaluate final render results, let's say variable _item_ contains these values: 
```json
{"name":"X-Men","rating":{"value":"7.4","weight":537121,"scale":10},"releasedYear":2000,"countries":[{"id":1,"name":"USA","code":"US"}],"genres":[{"id":1,"name":"Action"},{"id":2,"name":"Adventure"},{"id":10,"name":"Sci-Fi"}],"description":"In a world where mutants (evolved super-powered humans) exist and are discriminated against, two groups form for an inevitable clash: the supremacist Brotherhood, and the pacifist X-Men."}
```
Then rendering the following templates will return these:
```twig
&#127902; Watch/download {{ item.name }} {{ renderIfEverythingIsNotEmpty('&#11088;%%s/%%s', [item.rating.value, item.rating.scale])|delimiter }} {{ arraySlice('3', item.genres, 'name')|comma|delimiter }} {{ trimOnWord('20', item.description) }}
{# 🎞 Watch/download X-Men ⭐7.4/10. Action, Adventure, Sci-Fi. In a world where mutants (evolved super-powered humans) exist and are discriminated against, two groups form for an inevitable clash: .. #}

HD Stream &#128250; {{ item.name|delimiter }} {{ item.releasedYear }}
{# HD Stream 📺 X-Men - 2000 #}

Download {{ item.name|delimiter }} {{ arraySlice('2', item.countries, 'name')|comma|delimiter }} {{ item.releasedYear|delimiter }} {{ arraySlice('3', item.genres, 'name')|comma }}
{# Download X-Men. USA. 2000. Action, Adventure, Sci-Fi #}

https://google.com?q={{ ('Download ' ~ item.name)|url_encode }}
{# https://google.com?q=Download%20X-Men #}
```