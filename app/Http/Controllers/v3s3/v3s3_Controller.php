<?php

namespace App\Http\Controllers\v3s3;

use finfo;

use App\Http\Controllers\Controller as LV5_Controller;
use Illuminate\Http\Request as LV5_Request;

use App\v3s3\v3s3;

use App\Helpers\v3s3\v3s3_html;
use App\Helpers\v3s3\v3s3_xml;

use App\Exceptions\v3s3\v3s3_Exception;

class v3s3_Controller extends LV5_Controller
{
	public function put(LV5_Request $request) {
		$name = $request->path();

		try {
			if (empty($name) || ($name == '/')) {
				throw new v3s3_Exception(__('v3s3_Translation.V3S3_EXCEPTION_PUT_EMPTY_OBJECT_NAME'), v3s3_Exception::PUT_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new v3s3_Exception(__('v3s3_Translation.V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_Exception $e) {
			return response(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage(),
				]
			);
		}

		$data = $request->getContent();
		$content_type = $request->header('Content-type');
		$mime_type = (is_null($content_type)?(new finfo(FILEINFO_MIME))->buffer($data):$content_type);
		$v3s3 = new v3s3;
		$row = $v3s3->put(
			[
				'ip'=>$request->ip(),
				'name'=>$name,
				'data'=>$data,
				'mime_type'=>$mime_type,
			]
		);

		return response(
			[
				'status'=>1,
				'message'=>__('v3s3_Translation.V3S3_MESSAGE_PUT_OBJECT_ADDED_SUCCESSFULLY'),
			]
		)->header('v3s3-object-id', $row->id);
	}

	public function get(LV5_Request $request) {
		$name = $request->path();

		try {
			if (strlen($name) > 1024) {
				throw new v3s3_Exception(__('v3s3_Translation.V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_Exception $e) {
			return response(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage(),
				]
			);
		}

		$input = $request->all();
		unset($input['download']);
		$v3s3 = new v3s3;
		$row = $v3s3->get(
			array_replace(
				$input,
				[
					'name'=>$name,
				]
			)
		);

		$response = null;

		if(!empty($row['status'])) {
			$response = response($row['data']);

			if(empty($row['mime_type'])) {
				$row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($row['data']);
			}
			$response->withHeaders(
				[
					'v3s3-object-id'=>$row['id'],
					'Content-Type'=>$row['mime_type'],
					'Content-Length'=>strlen($row['data']),
				]
			);
			if(!empty($request->input('download'))) {
				$filename = basename($name);
				$response->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
			}
		} else {
			$response = response(
				[
					'status'=>1,
					'results'=>0,
					'message'=>__('v3s3_Translation.V3S3_MESSAGE_404'),
				],
				404
			);
		}

		return $response;
	}
	public function delete(LV5_Request $request) {
		$name = $request->path();

		try {
			if (empty($name) || ($name == '/')) {
				throw new v3s3_Exception(__('v3s3_Translation.V3S3_EXCEPTION_DELETE_EMPTY_OBJECT_NAME'), v3s3_Exception::DELETE_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new v3s3_Exception(__('v3s3_Translation.V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_Exception $e) {
			return response(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage(),
				]
			);
		}

		$input = $request->all();
		$v3s3 = new v3s3;
		$row = $v3s3->api_delete(
			array_replace(
				$input,
				[
					'name'=>$name,
					'ip_deleted_from'=>$request->ip()
				]
			)
		);

		if(empty($row)) {
			return response(
				[
					'status'=>1,
					'results'=>0,
					'message'=>__('v3s3_Translation.V3S3_MESSAGE_NO_MATCHING_RESOURCES'),
				],
				404
			);
		} else {
			return response(
				[
					'status'=>1,
					'results'=>1,
					'message'=>__('v3s3_Translation.V3S3_MESSAGE_DELETE_OBJECT_DELETED_SUCCESSFULLY'),
				]
			)->header('v3s3-object-id', $row->id);
		}
	}

	public function post(LV5_Request $request) {
		$name = $request->path();

		$input = $request->getContent();
		$parsed_input = (!empty($input)?json_decode($input, true):[]);
		if(!empty($input) && empty($parsed_input)) {
			try {
				throw new v3s3_Exception(__('v3s3_Translation.V3S3_EXCEPTION_POST_INVALID_REQUEST'), v3s3_Exception::POST_INVALID_REQUEST);
			} catch(v3s3_Exception $e) {
				return response(
					[
						'status'=>0,
						'code'=>$e->getCode(),
						'message'=>$e->getMessage(),
					]
				);
			}
		}

		$attr = (!empty($parsed_input['filter'])?$parsed_input['filter']:[]);
		if(!empty($name) && ($name != '/')) {
			$attr['name'] = $name;
		}

		$v3s3 = new v3s3;
		$rows = $v3s3->post(
			$attr
		);

		if(!empty($rows)) {
			foreach ($rows as &$_row) {
				unset($_row['id']);
				unset($_row['timestamp']);
				unset($_row['hash_name']);
				unset($_row['timestamp_deleted']);
				if(empty($_row['mime_type'])) {
					$_row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($_row['data']).' (determined using PHP finfo)';
				}
				unset($_row['data']);
			}

			$format = ((!empty($parsed_input['format'])&&in_array($parsed_input['format'], ['json', 'xml', 'html']))?strtolower($parsed_input['format']):'json');
			switch($format) {
				case 'xml':
					$rows = v3s3_xml::simple_xml($rows);
					return response($rows)->header('Content-Type', 'text/xml; charset=utf-8');
					break;
				case 'html':
					$rows = v3s3_html::simple_table($rows);
					return response($rows)->header('Content-Type', 'text/html; charset=utf-8');
					break;
				case 'json':
				default:
					return response()->json($rows, 200, array(), JSON_PRETTY_PRINT);
					break;
			}
		} else {
			return response(
				[
					'status'=>1,
					'results'=>0,
					'message'=>__('v3s3_Translation.V3S3_MESSAGE_NO_MATCHING_RESOURCES'),
				]
			);
		}
	}
}
