# Documentation

## Administrator usage

Pre-requisites

1. Configure a Typesense search server / endpoint
1. Get a Typesense administration API key + store in environment
1. Optionally create a search-only API key, for creation of scoped search keys
1. Set up collection(s)
1. Index the collection(s)

## Author usage

On a search page you will see a "Typesense" tab:

1. Choose the collection
1. Set the results per page value (e.g 10)
1. Use as a global search - projects that have a global search field will display query results on this page

Test the search and results on the draft stage prior to publishing changes, fine tune before publishing.

### Advanced

1. Use an Advanced Search Form (WIP)
1. Add a search scope
1. Add a search-only API key (if the environment has a search-only API key, this will be used).


## Search results configuration

Each page will have the following fields available on a "Search results" tab.

1. Subtitle for search result
1. Primary label/category for search result
1. Tags/labels for search result
1. Image used for search result

Projects will implement this differently, consult your project documentation to understand how these fields are used when displaying results.