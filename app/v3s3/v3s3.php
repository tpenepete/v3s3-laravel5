<?php

namespace App\v3s3;

use Illuminate\Database\Eloquent\Model as LV5_Model;

class v3s3 extends LV5_Model {
	public $timestamps = false;

	protected $connection = 'mysql-v3s3';
	protected $table = 'store';
	protected $fillable = [
		'timestamp'=>'timestamp',
		'date_time'=>'date_time',
		'ip'=>'ip',
		'hash_name'=>'hash_name',
		'name'=>'name',
		'data'=>'data',
		'mime_type'=>'mime_type',
		'status'=>'status',
		'timestamp_deleted'=>'timestamp_deleted',
		'date_time_deleted'=>'date_time_deleted',
		'ip_deleted_from'=>'ip_deleted_from',
	];

	public function put(Array $attr) {
		$attr = array_intersect_key($attr, $this->fillable);
		$attr['timestamp'] = (isset($attr['timestamp'])?$attr['timestamp']:time());
		$attr['date_time'] = date('Y-m-d H:i:s O', $attr['timestamp']);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		$attr['status'] = (isset($attr['status'])?$attr['status']:1);
		unset($attr['id']);

		$this->fill($attr);

		$this->save();

		return $this;
	}

	public function get(Array $attr) {
		$attr = array_intersect_key($attr, $this->fillable);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		unset($attr['name']);

		$row = $this->where($attr)->orderBy('id', 'desc');

		$row_count = $row->count();
		if(empty($row_count)) {
			return false;
		}

		return $row->first();
	}

	public function api_delete(Array $attr) {
		$attr = array_intersect_key($attr, $this->fillable);
		$attr['timestamp_deleted'] = (isset($attr['timestamp_deleted'])?$attr['timestamp_deleted']:time());
		$attr['date_time_deleted'] = date('Y-m-d H:i:s O', $attr['timestamp_deleted']);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		$attr['status'] = (isset($attr['status'])?$attr['status']:0);
		unset($attr['name']);

		$where = $attr;
		unset($where['status']);
		unset($where['timestamp_deleted']);
		unset($where['date_time_deleted']);
		unset($where['ip_deleted_from']);
		$row = $this->where($where)->orderBy('id', 'desc');

		$row_count = $row->count();
		if(empty($row_count)) {
			return false;
		}

		$row->fill(array_replace($row->first()->toArray(), $attr));
		$row->save();

		return $row;
	}

	public function post(Array $attr) {
		$attr = array_intersect_key($attr, $this->fillable);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		unset($attr['name']);

		$rows = $this->where($attr)->get()->toArray();

		return (!empty($rows)?$rows:[]);
	}
}
