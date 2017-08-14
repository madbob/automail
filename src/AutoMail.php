<?php

/*
	AutoMail
	Copyright (C) 2017  Roberto Guido <bob@linux.it>

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace AutoMail;

class AutoMail
{
	private static function parseXmlNode($email, $element)
	{
		$data = [];
		
		foreach ($element->attributes() as $name => $attribute) {
			switch ($name) {
				case 'type':
					$data['protocol'] = strtoupper((string) $attribute);
					break;
			}
		}

		$nodes = $element->children();
		foreach ($nodes as $name => $node) {
			switch($name) {
				case 'hostname':
				case 'port':
				case 'authentication':
				case 'socketType':
					$data[$name] = (string) $node;
					break;
				case 'username':
					$value = (string) $node;

					switch($value) {
						case '%EMAILADDRESS%':
							$data['username'] = $email;
							break;
						case '%EMAILLOCALPART%':
							$data['username'] = substr($email, 0, strpos($email, '@'));
							break;
						default:
							$data['username'] = $value;
							break;
					}

					break;
			}
		}
		
		return $data;
	}

	private static function parseXml($email, $contents)
	{
		$contents = utf8_encode($contents);
		libxml_use_internal_errors();
		
		try {
			$xml = new \SimpleXMLElement($contents, LIBXML_NOERROR | LIBXML_NOWARNING);
		}
		catch(\Exception $e) {
			return null;
		}

		$ret = [];

		$elements = $xml->xpath("//incomingServer");
		if (!is_null($elements)) {
			$ret['incoming'] = [];

			foreach ($elements as $element) {
				$data = self::parseXmlNode($email, $element);
				$ret['incoming'][] = $data;
			}
		}

		$elements = $xml->xpath("//outgoingServer");
		if (!is_null($elements)) {
			$ret['outgoing'] = [];

			foreach ($elements as $element) {
				$data = self::parseXmlNode($email, $element);
				$ret['outgoing'][] = $data;
			}
		}
		
		return $ret;
	}

	private static function initCurlHandle($url)
	{
		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => false,
			CURLOPT_USERAGENT => 'PHP AutoMail'
		]);

		return $ch;
	}

	private static function queryDomain($email)
	{
		$domain = substr(strrchr($email, "@"), 1);

		$ispdb_url = sprintf('https://live.mozillamessaging.com/autoconfig/v1.1/%s', $domain);
		$ch_ispdb = self::initCurlHandle($ispdb_url);
		$isp_url = sprintf('http://%s/.well-known/autoconfig/mail/config-v1.1.xml', $domain);
		$ch_isp = self::initCurlHandle($isp_url);

		$mh = curl_multi_init();
		curl_multi_add_handle($mh, $ch_ispdb);
		curl_multi_add_handle($mh, $ch_isp);

		$active = null;

		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($active > 0);

		$isp_contents = curl_multi_getcontent($ch_isp);
		$results = self::parseXml($email, $isp_contents);
		if ($results == null) {
			$ispdb_contents = curl_multi_getcontent($ch_ispdb);
			$results = self::parseXml($email, $ispdb_contents);
		}

		curl_multi_remove_handle($mh, $ch_ispdb);
		curl_multi_remove_handle($mh, $ch_isp);
		curl_multi_close($mh);
		curl_close($ch_ispdb);
		curl_close($ch_isp);
		
		return $results;
	}

	public static function discover($email)
	{
		$contents = self::queryDomain($email);
		if ($contents == null) {
			throw new NotFoundException();
			return;
		}
		
		return $contents;
	}
}

