<?php

namespace Novutec\WhoisParser\Templates\Type;

use Novutec\WhoisParser\Exception\ReadErrorException;

/**
 * Parser based on simple responses containing only 'key: value' entries.
 * An alternative to the default regex-blocks based parser that allows us to not care about missing entries
 * or the order of entries
 *
 * @package Novutec\WhoisParser\Templates\Type
 */
abstract class KeyValue extends AbstractTemplate
{

    protected $data = array();

    protected $regexKeys = array();


    /**
     * @param \Novutec\WhoisParser\Result\Result $result
     * @param $rawdata
     * @throws \Novutec\WhoisParser\Exception\ReadErrorException if data was read from the whois response
     */
    public function parse($result, $rawdata)
    {
        $this->parseRateLimit($rawdata);

        // check availability upon type - IP addresses are always registered
        if (isset($this->available) && strlen($this->available)) {
            preg_match_all($this->available, $rawdata, $matches);

            $result->addItem('registered', empty($matches[0]));
        }

        $rawdata = explode("\n", $rawdata);
        foreach ($rawdata as $line) {
            $line = trim($line);
            $lineParts = explode(':', $line, 2);
            if (count($lineParts) < 2) {
                continue;
            }

            $key = trim($lineParts[0]);
            $value = trim($lineParts[1]);

            if (array_key_exists($key, $this->data)) {
                if (! is_array($this->data[$key])) {
                    $this->data[$key] = array($this->data[$key]);
                }
                $this->data[$key][] = $value;
                continue;
            }

            $this->data[$key] = $value;
        }

        $this->reformatData();

        $matches = 0;
        foreach ($this->data as $key => $value) {
            foreach ($this->regexKeys as $dataKey => $regexList) {
                if (! is_array($regexList)) {
                    $regexList = array($regexList);
                }

                foreach ($regexList as $regex) {
                    if (preg_match($regex, $key)) {
                        $matches++;
                        $result->addItem($dataKey, $value);
                        break 2;
                    }
                }
            }
        }

        if ($matches < 1) {
            throw new ReadErrorException("Template did not correctly parse the response");
        }
    }


    /**
     * Perform any necessary reformatting of data (for example, reformatting dates)
     */
    protected function reformatData()
    {
    }
}