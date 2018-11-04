<?php

namespace Joshua19\StaticMapSaver\AirTable;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;

class API
{
    /** @var string  */
    protected $base_uri = 'https://api.airtable.com/v0/';

    /** @var \GuzzleHttp\Client  */
    protected $client;

    /** @var array  */
    protected $client_headers = [];

    /** @var array filter params [name=>value,...] */
    protected $filter_parameters = [];

    /** @var array  */
    protected $request_data = [];

    /** @var string  */
    protected $airtable_org_id;

    /** @var  string private API key */
    protected $api_key;

    /**
     * @var bool $verify_ssl ~ will load Client
     */
    protected $verify_ssl = true;

    /**
     * API constructor.
     *
     * @param string $airtable_org_id
     * @param string $api_key
     * @param string $base_uri ~ default: https://api.airtable.com/v0/
     */
    public function __construct($airtable_org_id, $api_key, $base_uri='https://api.airtable.com/v0/')
    {
        $this->airtable_org_id = $airtable_org_id;
        $this->api_key = $api_key;
        $this->base_uri = $base_uri;
        $this->client_headers = ['Authorization' => 'Bearer '.$this->api_key];
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function addFilter($name, $value)
    {
        $this->filter_parameters[$name] = $value;
        return $this;
    }


    /**
     * @param array $fields     array of strings
     * Only data for fields whose names are in this list will be included in the records.
     * If you don't need every field, you can use this parameter to reduce the amount of data transferred.
     * @return $this
     */
    public function setQueryFields(array $fields)
    {
        return $this->addFilter('fields', $fields);
    }

    /**
     * @param string $filter_by_formula
     * A formula used to filter records. The formula will be evaluated for each record,
     * and if the result is not 0, false, "", NaN, [], or #Error! the record will be included in the response.
     * If combined with view, only records in that view which satisfy the formula will be returned.
     * For example, to only include records where Place Lookup isn't empty, pass in: NOT({Place Lookup} = '')
     * @return API
     */
    public function setQueryFilterByFormula(string $filter_by_formula)
    {
        return $this->addFilter('filterByFormula', $filter_by_formula);
    }

    /**
     * @param int $limit
     * The maximum total number of records that will be returned in your requests.
     * If this value is larger than pageSize (which is 100 by default), you may have to load multiple pages to
     * reach this total. See the Pagination section below for more.
     * @return API
     */
    public function setQueryMaxRecords(int $limit)
    {
        return $this->addFilter('maxRecords', $limit);
    }

    /**
     * @param int $page_size
     * Note this appears to be the same as maxRecords
     * The number of records returned in each request. Must be less than or equal to 100. Default is 100. See the Pagination for more.
     * @return API
     */
    public function setQueryPageSize(int $page_size=100)
    {
        return $this->addFilter('pageSize', $page_size);
    }

    /**
     * @param int $offset start at 0
     * @return API
     */
    public function setOffset($offset=0)
    {
        return $this->addFilter('offset', $offset);
    }

    /**
     * @param array $sort
     * A list of sort objects that specifies how the records will be ordered.
     * Each sort object must have a field key specifying the name of the field to sort on, and an optional direction key
     * that is either "asc" or "desc". The default direction is "asc".
     *
     * For example, to sort records by Place Lookup, pass in:
     * [{field: "Place Lookup", direction: "desc"}]
     * If you set the view parameter, the returned records in that view will be sorted by these fields.
     *
     * @return $this
     */
    public function setQuerySort(array $sort)
    {
        return $this->addFilter('sort', $sort);
    }

    /**
     * @param string $view
     * The name or ID of a view in the Places table. If set, only the records in that view will be returned.
     * The records will be sorted according to the order of the view.
     * @return API
     */
    public function setQueryView(string $view)
    {
        return $this->addFilter('view', $view);
    }

    /**
     * @param string $cell_format
     * The format that should be used for cell values. Supported values are:
     * "json": cells will be formatted as JSON, depending on the field type.
     * "string": cells will be formatted as user-facing strings, regardless of the field type.
     * Note: You should not rely on the format of these strings, as it is subject to change.
     * The default is "json".
     * @return API
     */
    public function setQueryCellFormat(string $cell_format='json')
    {
        return $this->addFilter('cellFormat', $cell_format);
    }

    /**
     * @param string $time_zone
     * The time zone that should be used to format dates when using "string" as the cellFormat.
     * This parameter is required when using "string" as the cellFormat.
     * @return API
     */
    public function setQueryTimeZone(string $time_zone)
    {
        return $this->addFilter('timeZone', $time_zone);
    }

    /**
     * @param string $user_locale
     * The user locale that should be used to format dates when using "string" as the cellFormat.
     * This parameter is required when using "string" as the cellFormat.
     * @return API
     */
    public function setQueryUserLocale(string $user_locale)
    {
        return $this->addFilter('userLocal', $user_locale);
    }

    // @TODO Pagination

    /**
     * @param string $method
     * @param string $table
     * @param array $options
     *
     * @return bool|\GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doRequest($method, $table, $options=[])
    {
        // should this be limited to just GET?
        if ( $method == 'GET' ) {
            $params = [];
            if (isset($options['query']) ) {
                $params = $options['query'];
            }
            $options['query'] = $this->buildQuery($params);
        }

        $path = $this->airtable_org_id.'/'.$table;

        $this->request_data = [
            'method' => $method,
            'path' => $table,
            'options' => $options
        ];

        $response = false;
        try {
            if (!is_object($this->client) || !$this->client instanceof Client) {
                $this->loadClient();
            }
            $response = $this->client->request($method, $path, $options);

        } catch (RequestException $exception) {
            // @TODO log error
            echo Psr7\str($exception->getRequest());
            if ($exception->hasResponse()) {
                echo Psr7\str($exception->getResponse());
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return $this->request_data;
    }






    /**
     * @param bool $verify_ssl
     * @return API
     */
    public function setVerifySsl(bool $verify_ssl): API
    {
        $this->verify_ssl = $verify_ssl;
        return $this;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function buildQuery($params):array
    {
        foreach ($this->filter_parameters as $name => $value) {
            $params[$name] = $value;
        }
        return $params;
    }

    /**
     *
     */
    protected function loadClient()
    {
        $this->client = new Client([
            'base_uri' => $this->base_uri, // Base URI is used with relative requests
            'timeout' => 15.0, // You can set any number of default request options.
            'http_errors' => false, // http://docs.guzzlephp.org/en/latest/request-options.html#http-errors
            'verify' => $this->verify_ssl, // local windows machines sometimes give issues here
            'headers' => $this->client_headers, // http://docs.guzzlephp.org/en/latest/request-options.html#headers
            'version' => 1.0 // http://docs.guzzlephp.org/en/latest/request-options.html#version
        ]);
    }
}