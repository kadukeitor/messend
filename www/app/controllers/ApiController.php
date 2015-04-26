<?php

	namespace controllers;

	use models\MessageModel;
	use models\ServerModel;

	/**
	 * Class ApiController
	 * @package controllers
	 */
	class ApiController {

		function main( \Base $f3 ) {

			echo "<code>tasksend</code>";

		}

		function register( \Base $f3 ) {

			$request = $f3->get( 'REQUEST' );
			$f3->scrub( $request );

			$server = isset( $request['server'] ) ? $request['server'] : 'unknow';
			$user   = isset( $request['user'] ) ? $request['user'] : 'unknow';

			$server_model = new ServerModel();

			$response = $server_model->register( $server, $user );

			header( 'Content-type: application/json' );
			echo json_encode( $response );

		}

		function message( \Base $f3 ) {

			$request = $f3->get( 'REQUEST' );
			$f3->scrub( $request );

			$key     = isset( $request['key'] ) ? $request['key'] : 'unknow';
			$message = isset( $request['message'] ) ? $request['message'] : '';

			$message_model = new MessageModel();

			$response = $message_model->add( $key, $message );

			header( 'Content-type: application/json' );
			echo json_encode( $response );


		}

	}