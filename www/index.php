<?php

	$f3 = require( 'lib/base.php' );

	$f3->set( 'DEBUG', 3 );
	$f3->set( 'UI', "views/" );
	$f3->set( 'TEMP', "data/temp//" );
	$f3->set( 'CACHE', "folder=data/cache/" );
	$f3->set( 'LOGS', "data/log/" );
	$f3->set( 'DB', new DB\Jig( 'data/db/', DB\Jig::FORMAT_JSON ) );

	$f3->route( 'GET /',
		/**
		 * Main
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {
			echo "<code>recho</code>";
		}
	);

	$f3->route( 'GET /login',
		/**
		 * Login
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {
			echo \Template::instance()->render( 'page-login.html' );
		}
	);

	$f3->route( 'POST /check_login',
		/**
		 * Login
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {

			$post = $f3->get( 'POST' );
			$f3->scrub( $post );

			$key = strtolower( $post['key'] );

			// Session's Mapper
			$sessions = new DB\Jig\Mapper( $f3->get( 'DB' ), 'sessions' );

			// Checking Key
			if ( $sessions->load( array( '@_id=?', $key ) ) ) {
				$f3->set( 'SESSION.key', $key );
				$f3->reroute( '/dashboard' );
			} else {
				$f3->reroute( '/login?' . http_build_query( array( 'error' => base64_encode( "Bad Key" ) ) ) );
			}

		}
	);

	$f3->route( 'GET /logout',
		/**
		 * Logout
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {
			$f3->clear( 'SESSION' );
			$f3->reroute( '/login' );
		}
	);

	$f3->route( 'GET /dashboard',
		/**
		 * Dashboard
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {

			if ( ! $f3->exists( 'SESSION.key' ) ) {
				$f3->reroute( '/login?' . http_build_query( array( 'error' => base64_encode( "Bad Permissions" ) ) ) );
			}

			$key = $f3->get( 'SESSION.key' );

			// Session's Mapper
			$sessions = new DB\Jig\Mapper( $f3->get( 'DB' ), 'sessions' );

			// Sessions
			$sessions->load( array( '@_id=?', $key ) );

			// Server
			$server = $sessions->server;

			// Message's Mapper
			$messages = new DB\Jig\Mapper( $f3->get( 'DB' ), 'messages_' . $server );

			// Messages of the User
			$messages_user = [ ];
			foreach (
				$messages->find( array(
					'@session=?',
					$key
				), array( 'order' => 'datetime SORT_DESC' ) ) as $message
			) {
				unset( $message->session );
				$messages_user[] = $message->cast();
			}

			// Messages on the Server
			$messages_server = [ ];
			foreach (
				$messages->find( array(
					'@server=?',
					$server
				), array( 'order' => 'datetime SORT_DESC' ) ) as $message
			) {
				unset( $message->session );
				$messages_server[] = $message->cast();
			}

			$f3->set( 'messages_user', $messages_user );
			$f3->set( 'messages_server', $messages_server );

			echo \Template::instance()->render( 'page-dashboard.html' );
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
				$messages           = new DB\Jig\Mapper( $f3->get( 'DB' ), 'messages_' . $sessions->server );
				$messages->session  = $key;
				$messages->server   = $sessions->server;
				$messages->user     = $sessions->user;
				$messages->datetime = date( "Y-m-d H:i:s" );
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

	$f3->route( 'GET /delete',
		/**
		 * Delete a Message
		 * The parameters key and id are required.
		 *
		 * @param Base $f3
		 */
		function ( \Base $f3 ) {

			$request = $f3->get( 'REQUEST' );
			$f3->scrub( $request );

			// Get Parameters
			$key = isset( $request['key'] ) ? $request['key'] : 'unknow';
			$id  = isset( $request['id'] ) ? $request['id'] : '';

			// Check key
			$sessions = new DB\Jig\Mapper( $f3->get( 'DB' ), 'sessions' );

			if ( ! $sessions->load( array( '@_id=?', $key ) ) ) {

				// Error Response
				$response = array( "status" => "error", "error" => "The key is invalid" );

			} else {

				$messages = new DB\Jig\Mapper( $f3->get( 'DB' ), 'messages_' . $sessions->server );

				if ( $messages->load( array( '@_id=?', $id ) ) ) {

					// Erase the Message
					$messages->erase( array( '@_id=?', $id ) );

					$response = array( "status" => "success" );

				} else {

					// Error Response
					$response = array( "status" => "error", "error" => "The Message is invalid" );

				}

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
				$messages = new DB\Jig\Mapper( $f3->get( 'DB' ), 'messages_' . $server );
				$response = [ ];
				foreach (
					$messages->find( array(
						'@server=?',
						$server
					), array( 'order' => 'datetime SORT_DESC', 'limit' => 20 ) ) as $message
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

	$f3->route( 'GET /test',
		/**
		 * Test the System ( inserting 10000 messages ).
		 * The parameters key is required.
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

				// Insert the Messages
				$messages = new DB\Jig\Mapper( $f3->get( 'DB' ), 'messages_' . $sessions->server );
				for ( $i = 0; $i < 10000; $i ++ ) {

					$web = new Web();

					$messages->reset();
					$messages->session  = $key;
					$messages->server   = $sessions->server;
					$messages->user     = $sessions->user;
					$messages->datetime = date( "Y-m-d H:i:s" );
					$messages->message  = $web->filler( 1, 5, false );
					$messages->insert();
				}

			}


		}
	);

	$f3->run();
