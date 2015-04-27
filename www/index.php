<?php

	$f3 = require( 'lib/base.php' );

	$f3->set( 'CACHE', "folder=data/cache/" );
	$f3->set( 'LOGS', "data/log/" );
	$f3->set( 'DB', new DB\Jig( 'data/db/', DB\Jig::FORMAT_JSON ) );

	$f3->route( 'GET /',
		/**
		 * Main Route
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {
			echo "<code>MesSend</code>";
		}
	);

	$f3->route( 'GET /register',
		/**
		 * Register Sessions
		 * The parameters user and server are required
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {

			$request = $f3->get( 'REQUEST' );
			$f3->scrub( $request );

			// Get Parameters
			$server = isset( $request['server'] ) ? $request['server'] : 'unknow';
			$user   = isset( $request['user'] ) ? $request['user'] : 'unknow';


			$sessions = new DB\Jig\Mapper( $f3->get( 'DB' ), 'sessions' );
			// Check if the Session exist
			if ( $sessions->count( array( '@server=? AND @user=?', $server, $user ) ) ) {

				// Load Session
				$sessions->load( array( '@server=? AND @user=?', $server, $user ) );

			} else {

				// Create Session
				$sessions->server = $server;
				$sessions->user   = $user;
				$sessions->save();

			}

			// Return Session
			$response = $sessions->cast();
			header( 'Content-type: application/json' );
			echo json_encode( $response );

		}
	);

	$f3->route( 'GET /message',
		/**
		 * Save a Message
		 * The parameters key and message are required.
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {

			$request = $f3->get( 'REQUEST' );
			$f3->scrub( $request );

			// Get Parameters
			$key     = isset( $request['key'] ) ? $request['key'] : 'unknow';
			$message = isset( $request['message'] ) ? $request['message'] : '';

			// Check key
			$sessions = new DB\Jig\Mapper( $f3->get( 'DB' ), 'sessions' );

			if ( ! $sessions->load( array( '@_id=?', $key ) ) ) {

				// Error Response
				$response = array( "error" => "The key is invalid" );

			} else {

				// Insert the Message
				$messages           = new DB\Jig\Mapper( $f3->get( 'DB' ), 'messages' );
				$messages->session  = $key;
				$messages->server   = $sessions->server;
				$messages->user     = $sessions->user;
				$messages->datetime = date("Y-m-d H:i:s");
				$messages->message  = $message;
				$messages->insert();

				// Hide the Session
				unset( $messages->session );

				$response = $messages->cast();
			}

			// Return the Message
			header( 'Content-type: application/json' );
			echo json_encode( $response );

		}
	);

	$f3->route( 'GET /status',
		/**
		 * All messages for the Server
		 * The parameter key is required
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {

			$request = $f3->get( 'REQUEST' );
			$f3->scrub( $request );

			// Get Parameters
			$key = isset( $request['key'] ) ? $request['key'] : 'unknow';

			// Check key
			$sessions = new DB\Jig\Mapper( $f3->get( 'DB' ), 'sessions' );

			if ( ! $sessions->load( array( '@_id=?', $key ) ) ) {

				// Error Response
				$response = array( "error" => "The key is invalid" );

			} else {

				$server = $sessions->server;

				// Return all Messages for the Server
				$messages = new DB\Jig\Mapper( $f3->get( 'DB' ), 'messages' );
				$response = [ ];
				foreach (
					$messages->find( array(
						'@server=?',
						$server
					), array( 'order' => 'datetime SORT_DESC', 'limit' => 10 ) ) as $message
				) {
					unset( $message->session );
					$response[] = $message->cast();
				}

			}

			// Return Session
			header( 'Content-type: application/json' );
			echo json_encode( $response );

		}
	);

	$f3->run();
