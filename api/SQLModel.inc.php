<?php

use Aws\Common\Aws;
use Aws\Common\Enum\Region;
use Guzzle\Service\Resource\ResourceIteratorInterface;
use Aws\S3\S3Client;
use Guzzle\Http\EntityBody;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\S3\Model\ClearBucket;

require_once 'Database.inc.php';

class Model implements JsonSerializable
{
	protected $_data = [];
	protected static $_schema = [];
	protected static $_primary_keys = [];

	public static function CamelCaseFromUnderscore($word,$capitalize_first=false)
	{
		$word = preg_replace_callback(
        "/(^|_)([a-z])/",
        function($m) { return strtoupper("$m[2]"); },
        $word
    	);
    	if(!$capitalize_first)
    	{
    		$word = lcfirst($word);
    	}
    	return $word;
	}

	public static function UnderscoreFromCamelCase($word)
	{
		return $word = preg_replace_callback(
        	"/(^|[a-z])([A-Z])/",
        	function($m) { return strtolower(strlen($m[1]) ? "$m[1]_$m[2]" : "$m[2]"); },
        $word);
		//return preg_replace('/([A-Z])/e', "'_' . strtolower('\\1')", $str);
	}

	public static function tablename()
	{
		return Model::UnderscoreFromCamelCase(get_called_class()).'s';
	}

	public static function tables()
	{
		global $db;

		$tables_query = <<<QUERY
SELECT n.nspname as "Schema",
  c.relname as "Name",
  CASE c.relkind WHEN 'r' THEN 'table' WHEN 'v' THEN 'view' WHEN 'm' THEN 'materialized view' WHEN 'i' THEN 'index' WHEN 'S' THEN 'sequence' WHEN 's' THEN 'special' WHEN 'f' THEN 'foreign table' END as "Type",
  pg_catalog.pg_get_userbyid(c.relowner) as "Owner"
FROM pg_catalog.pg_class c
     LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
WHERE c.relkind IN ('r')
      AND n.nspname <> 'pg_catalog'
      AND n.nspname <> 'information_schema'
      AND n.nspname !~ '^pg_toast'
  AND pg_catalog.pg_table_is_visible(c.oid)
ORDER BY 1,2;
QUERY;

		$stmt = $db->prepare($tables_query);
		if($stmt->execute([]))
		{
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}

	public static function columns()
	{
		$class = get_called_class();
		if(empty($class::$_schema))
		{
			$table = $class::tablename();
			$query = <<<CQUERY
SELECT column_name as name, data_type as type, is_nullable as nullable, column_default as default_value, (SELECT count(*)
FROM   pg_attribute a
LEFT OUTER JOIN  pg_index i  ON a.attrelid = i.indrelid
                     AND a.attnum = ANY(i.indkey)
WHERE  i.indrelid = '{$table}'::regclass
AND    i.indisprimary AND a.attname=column_name) as pk 
FROM information_schema.columns where table_name='{$table}'
CQUERY;
			$db = $class::database();
			$stmt = $db->prepare($query);
			$stmt->execute([]);
			$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$class::$_schema = [];
			$class::$_primary_keys = [];
			foreach($columns as $row)
			{
				$class::$_schema[$row['name']] = $row;
				if($row['pk'])
				{
					$class::$_primary_keys[] = $row['name'];
				}
			}
		}
		return $class::$_schema;
	}

	public static function requiredColumnNames()
	{
		$columns = self::columns();
		$names = [];
		foreach($columns as $name => $def)
		{
			if($def['nullable'] == 'NO' && $def['default_value'] == null)
			{
				$names[] = $name;
			}
		}
		return $names;
	}

	public function takeValues($values)
	{
		$class = get_class($this);
		$column_names = $class::column_names();
		foreach($column_names as $n)
		{
			if(array_key_exists($n, $values))
			{
				$this->$n = $values[$n];
			}
		}
		foreach($values as $k => $v)
		{
			if(!in_array($k, $column_names) && method_exists($this, ($method = 'set'.ucfirst($k))))
    		{
      			return $this->$method($v);
			}
		}
	}

	public static function column_names()
	{
		return array_keys(self::columns());
	}

	public static function primaryKeyColumns()
	{
		$class = get_called_class();
		if(empty($class::$_schema))
		{
			$class::columns();
		}
		return $class::$_primary_keys;
	}

	public static function find($keys=null,$order=null,$limit=0)
	{
		$class = get_called_class();
		$table = $class::tablename();
		$values = [];

		$query = "SELECT * from ${table}";

		if(is_array($keys))
		{
			$query .= " WHERE ";
			$wheres = [];
			foreach($keys as $key => $val)
			{
				$wheres[] = "{$key} = ?";
				$values[] = $val;
			}	
			$query .= implode(' AND ', $wheres);

		}
		else if(!is_null($keys)) // assume primary key
		{
			$query .= " WHERE id = ?";
			$values[] = $keys;
			$limit = 1;
		}
		else // get em all
		{

		}

		if(!empty($order))
		{
			if(!is_array($order))
			{
				$order = [$order];
			}
			$query .= " ORDER BY ";
			$query .= implode(', ',$order);
		}

		if($limit)
		{
			$query .= " LIMIT {$limit}";
		}

		return $class::findBySql($query,$values,$limit==1);
	}

	public static function findBySql($query,$params,$unwrap=false)
	{
		$class = get_called_class();
		$db = $class::database();
		$stmt = $db->prepare($query);
		$stmt->execute($params);
		$objects = $stmt->fetchAll(PDO::FETCH_CLASS,$class);
		unset($stmt);
		if($unwrap)
		{
			if(count($objects) == 1)
			{
				return $objects[0];
			}
			return null;
		}
		return $objects;
	}

	public static function all($order=null)
	{
		$class = get_called_class();
		return $class::find(null,$order);
	}

	public static function database()
	{
		global $db;
		return $db;
	}

	public function __construct()
	{
	}

	public function validateRequiredColumns()
	{
		$class = get_class($this);
		foreach($class::requiredColumnNames() as $n)
		{
			ValueOrDie($n,$this->_data);
		}
	}

	public function s3Prefix()
	{
		throw new Exception("Subclass Responsibility",500);
	}

	public function isNew()
	{
		$class = get_class($this);
		$pks = $class::primaryKeyColumns();
		foreach($pks as $pk)
		{
			if(isset($this->$pk) && !empty($this->$pk))
			{
				return false;
			}
		}
		return true;
	}

	public function remove($force = false)
	{
		$class = get_class($this);
		$db = $class::database();
		$columns = $class::column_names();
		$table = $class::tablename();
		$columns_to_update = [];
		$values = [];
		$pks = $class::primaryKeyColumns();
		$where = [];
		foreach($pks as $pk)
		{
			$where[] = "{$pk} = ?";
			$values[] = $this->$pk;
		}
		$wheres = implode(' AND ', $where);

		if((in_array('deleted_at', $columns) || in_array('active', $columns)) && !$force)
		{
			$sql = "UPDATE {$table} SET ";
			$updates = [];
			if(in_array('deleted_at',$column_names))
			{
				$updates[] = 'deleted_at = now()';
			}
			if(in_array('active', $column_names))
			{
				$updates[] = 'active = false';				
			}
			$ustr = implode(', ', $updates);
			$sql = "UPDATE {$table} SET {$ustr} WHERE {$wheres}";
			$stmt = $db->prepare($sql);
			$stmt->execute($values);
			unset($stmt);
		}
		else
		{
			
			$sql = "DELETE FROM {$table} WHERE {$wheres}";
			$stmt = $db->prepare($sql);
			$stmt->execute($values);
			unset($stmt);
		}
	}

	public function save()
	{
		$this->validateRequiredColumns();
		if($this->isNew())
		{
			$this->insert();
		}
		else
		{
			$this->update();
		}
	}

	public function insert()
	{
		$class = get_class($this);
		$columns = $class::columns();
		$table = $class::tablename();
		$db = $class::database();

		$col_names = [];
		$place_holders = [];
		$values = [];
		foreach($columns as $name => $def)
		{
			$type = $def['type'];
			if(isset($this->$name))
			{
				$col_names[] = $name;
				$place_holders[] = '?';
				$values[] = $this->$name;
			}
		}
		$cstr = implode(', ',$col_names);
		$pstr = implode(', ', $place_holders);
		$pks = $class::primaryKeyColumns();
		$pkstr = implode(', ', $pks);
		$query = "INSERT INTO {$table} ({$cstr}) VALUES ({$pstr}) RETURNING {$pkstr}";
		$stmt = $db->prepare($query);
		$stmt->execute($values);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		foreach($pks as $pk_name)
		{
			$this->$pk_name = $result[$pk_name];
		}
	}

	public function update()
	{
		$class = get_class($this);
		$columns = $class::columns();
		$pks = $class::primaryKeyColumns();
		$table = $class::tablename();
		$db = $class::database();

		$col_names = [];
		$where = [];
		$values = [];
		foreach($columns as $name => $def)
		{
			$type = $def['type'];
			if(isset($this->$name) && !in_array($name, $pks))
			{
				$col_names[] = $name . ' = ?';
				$values[] = $this->$name;
			}
		}
		foreach($pks as $name)
		{
			$where[] = $name . ' = ?';
			$values[] = $this->$name;
		}
		$cstr = implode(', ',$col_names);
		$wstr = implode(' AND ', $where);	


		$query = "UPDATE {$table} SET {$cstr} WHERE {$wstr}";
		//echo $query . json_encode($values);
		//exit;
		$stmt = $db->prepare($query);
		$stmt->execute($values);
		unset($stmt);
	}

	public function refresh()
	{
		$class = get_class($this);
		$columns = $class::columns();
		$pks = $class::primaryKeyColumns();
		$table = $class::tablename();
		$db = $class::database();
		$ps = [];
		$values = [];
		foreach($pks as $p)
		{
			$ps[] = $p . ' = ?';
			$values[] = $this->$p;
		}
		$where = implode(' AND ', $ps);
		$query = "SELECT * FROM {$table} WHERE {$where}";
		$stmt = $db->prepare($query);
		$stmt->execute($values);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($rows) == 1)
		{
			$r = $rows[0];
			foreach($r as $k => $v)
			{
				$this->$k = $v;
			}
		}
	}

	public function updateFromData($values)
	{
		global $s3;
		global $bucket;

		if(is_null($this->_data))
		{
			$this->_data = [];
		}
		$this->_data = array_merge([],$this->_data,$values);

		// here we are going to replace data urls with expiring urls to
		foreach($this->_data as $key => $v)
		{
			if(!empty($v) && strpos($v, "data:image/") === 0)
			{
				$type = explode("/", $v)[1];
				$ext = 'jpg';
				if ($type[0] == 'g') { $ext = 'gif'; }
				else if ($type[0] == 'p') { $ext = 'png'; }

				$v = str_replace("data:image/jpeg;base64,", "", $v);
				$v = str_replace("data:image/jpg;base64,", "", $v);
				$v = str_replace("data:image/png;base64,", "", $v);
				$v = str_replace("data:image/gif;base64,", "", $v);

				$decoded = base64_decode($v);

				$image = new Imagick();
				$image->readImageBlob($decoded);
				FixImageOrientation($image);

				// write the original image
				$tmpfile = tempnam('/tmp', $ext) . '.' . $ext;
				$image->writeImage($tmpfile);
				$image->destroy();

				$filename = UUID::mint(4) . "." . $ext;
				$s3key = "{$this->s3Prefix()}/{$filename}";

                $s3->upload($bucket,$s3key,file_get_contents($tmpfile),'public-read',['params'=>['ContentType' => "image/{$ext}"]]);
				//$result = $s3->putObject(array('Bucket' => $bucket, 'Key' => $s3key, 'Body' => EntityBody::factory(fopen($tmpfile, 'r'))));
				$this->_data[$key] = $filename;
				safe_unlink($tmpfile);
			}
		}
		return $this;
	}

	public function updateFromFiles($files)
	{
		global $bucket;
		global $s3;
		if(is_array($files))
		{
			$prefix = $this->s3Prefix();
			$expireDate = $this->expireDate();

			$keys = array_keys($files);
			foreach ($keys as $key)
			{
				if ((int)($files[$key]['size']) > 0)
				{
					$filename = $files[$key]["name"];
					if (!empty($filename))
					{
						$extension = FileExtension($filename);
						$filepath = tempnam("/tmp", "ns{$key}_") . "." . $extension;
						move_uploaded_file($files[$key]["tmp_name"], $filepath);
						$filename = '' . UUID::mint(4) . "." . $extension;
					    $s3key = "{$prefix}/{$filename}";
                        $s3->upload($bucket,$s3key,file_get_contents($filepath),'public-read',['params'=>['ContentType' => "image/{$extension}"]]);

                        //$result = $s3->putObject(array('Bucket' => $bucket,'Key'=> $s3key, 'Body' => EntityBody::factory(fopen($filepath,'r'))));
					    $this->_data[$key] = $filename;
						safe_unlink($filepath);
					}
				}
				else
				{
					if(!empty($this->_data[$key]) && !IsDevelopment())
					{
						throw new Exception("Missing File Data", 500);						
					}
				}
			}
		}
		return $this;
	}

	public function __get($name)
	{
		$method_name = self::CamelCaseFromUnderscore($name);
		$field_name = self::UnderscoreFromCamelCase($name);
		$fetcher_name = 'get'.ucfirst($method_name);

		if(method_exists($this,$fetcher_name) && !array_key_exists($field_name, $this->_data))
		{
			$this->_data[$name] = $this->$fetcher_name();
		}

		if(method_exists($this, ($method = $name)))
    	{
      		return $this->$method();
    	}

        if(array_key_exists($name, $this->_data)) 
        {
            return $this->_data[$name];
        }
        return null;
	}

	public function __set($name,$value)
	{
		if(method_exists($this, ($method = 'set'.ucfirst($name))))
    	{
      		return $this->$method($value);
    	}
		if(is_null($value))
		{
			unset($this->_data[$name]);
		}
		else
		{
			$this->_data[$name] = $value;
		}
		return $this;
	}

	public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

	public function jsonSerialize() 
	{
        return $this->_data;
    }
}