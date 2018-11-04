# Static Map Saver

Using Airtable API as the data feed to then build static map images from MapBox and then save it to disk. Static 
map images can then be used on web pages.

## Requirements

- PHP7 & Composer
- MapBox Key
- Airtable key
- Run from Command line

## Goals

- Configurable
- Follow consistent naming of images to be pulled from a front end web site


### File naming convention 

- {Place Lookup}-wide.png 
- {Place Lookup}-detail.png 

## References

- [Airtable](https://airtable.com/)
- [mapbox](https://www.mapbox.com/)
- [https://www.mapbox.com/api-documentation/#static](https://www.mapbox.com/api-documentation/#static)


## Config

- Keys
- Data source
- Airtable Endpoint
- Save images to path

Copy the sample.env and fill in all values with your instance data.


## Design notes

- Airtable could be broken into separate project
- Mapbox could also be broken into separate project

Created at [Faithtech Hack](https://www.faithtechhack.com/chicago)