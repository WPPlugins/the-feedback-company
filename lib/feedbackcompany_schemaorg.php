<?php
	class feedbackcompany_schemaorg
	{
		private $tree;

		function __construct($path = './')
		{
			$json = file_get_contents($path.'schema.jsonld');
			$json = json_decode($json, true);

			foreach ($json['@graph'] as $key => $value)
			{
				$id = $value['@id'];
				if (substr($id, 0, 18) == 'http://schema.org/')
					$id = substr($id, 18);

				if (!isset($this->tree[$id]))
					$this->tree[$id] = array();

				if (isset($value['rdfs:label']))
					$this->tree[$id]['label'] = $value['rdfs:label'];
				if (isset($value['rdfs:comment']))
					$this->tree[$id]['comment'] = $value['rdfs:comment'];
				if (isset($value['rdfs:subClassOf']))
					$this->addSchemas('subclass', $id, $value['rdfs:subClassOf']);

				if (isset($value['http://schema.org/domainIncludes']))
					$this->addSchemas('property', $id, $value['http://schema.org/domainIncludes']);

				if (isset($value['http://schema.org/rangeIncludes']))
				{
					$this->addSchemas('type', $id, $value['http://schema.org/rangeIncludes']);
				}
			}
		}

		function addSchema($type, $id, $sub_id)
		{
			if (substr($sub_id, 0, 18) == 'http://schema.org/')
				$sub_id = substr($sub_id, 18);

			if ($type == 'subclass')
			{
				if (!isset($this->tree[$id]['parent']))
					$this->tree[$id]['parent'] = array();
				$this->tree[$id]['parent'][] = $sub_id;
			}
			if ($type == 'type')
			{
				if (!isset($this->tree[$id]['type']))
					$this->tree[$id]['type'] = array();
				$this->tree[$id]['type'][] = $sub_id;
			}

			if (!isset($this->tree[$sub_id]))
				$this->tree[$sub_id] = array();

			if (!isset($this->tree[$sub_id][$type]))
				$this->tree[$sub_id][$type] = array();

			$this->tree[$sub_id][$type][] = $id;
		}
		function addSchemas($type, $id, $mix)
		{
			if (!is_array($mix))
				return;

			if (isset($mix['@id']))
				$this->addSchema($type, $id, $mix['@id']);
			else
				foreach ($mix as $value)
					$this->addSchemas($type, $id, $value);
		}

		function getSchema($id)
		{
			if (!isset($this->tree[$id]))
				return false;

			return $this->tree[$id];
		}

		function getProperties($id, $ret = array())
		{
			if (!isset($this->tree[$id]))
				return $ret;

			if (isset($this->tree[$id]['property']))
				foreach ($this->tree[$id]['property'] as $property)
					if (in_array($property, $this->getPropertiesImportant($id)) || ($id != 'LocalBusiness' && $id != 'Organization' && $id != 'Thing'))
						$ret[$property] = $this->getSchema($property);

			if (isset($this->tree[$id]['parent']))
				return $this->getProperties($this->tree[$id]['parent'][0], $ret);

			ksort($ret);
			return $ret;
		}

		function getPropertiesImportant($id)
		{
			return array(
				'name',
				'description',
				'image',
				'logo',
				'address',
				'telephone',
				'faxNumber',
				'url',
				'email',
				'vatID',
				'openingHours',
				'paymentAccepted',
				'currenciesAccepted',
				'priceRange',
			);
		}

		function getTree($id, $crumb = null, $ret = array())
		{
			if (!$crumb)
				$crumb = $id;

			$ret[] = $crumb;

			if (!isset($this->tree[$id]['subclass']))
				return $ret;

			foreach ($this->tree[$id]['subclass'] as $sub_id)
			{
				$ret = $this->getTree($sub_id, $crumb."\n".$sub_id, $ret);
			}

			sort($ret);
			return $ret;
		}
	}
