/**
 * Unstoppable Media Folders - admin script.
 *
 * Builds the folder sidebar on the Media Library, drives folder CRUD over
 * AJAX, filters media in both list and grid modes, and supports drag-and-drop
 * assignment. All requests carry the localized nonce.
 */
( function ( $ ) {
	'use strict';

	if ( typeof window.umfdData === 'undefined' ) {
		return;
	}

	var D       = window.umfdData;
	var S       = D.strings;
	var folders = [];      // Flat list of {id,name,parent,count}.
	var allCount = 0;
	var uncatCount = 0;

	/* --------------------------------------------------------------------
	 * AJAX helper
	 * ------------------------------------------------------------------ */
	function request( action, data ) {
		data = data || {};
		data.action = action;
		data.nonce  = D.nonce;
		return $.post( D.ajaxUrl, data );
	}

	/* --------------------------------------------------------------------
	 * Small UI helpers
	 * ------------------------------------------------------------------ */
	function esc( str ) {
		return $( '<div/>' ).text( str == null ? '' : String( str ) ).html();
	}

	function toast( message ) {
		var $t = $( '.umfd-toast' );
		if ( ! $t.length ) {
			$t = $( '<div class="umfd-toast" />' ).appendTo( 'body' );
		}
		$t.text( message ).addClass( 'is-visible' );
		window.clearTimeout( $t.data( 'timer' ) );
		$t.data( 'timer', window.setTimeout( function () {
			$t.removeClass( 'is-visible' );
		}, 2200 ) );
	}

	function depthOf( id ) {
		var depth = 0;
		var map   = {};
		$.each( folders, function ( i, f ) { map[ f.id ] = f; } );
		var current = map[ id ];
		while ( current && current.parent && map[ current.parent ] ) {
			depth++;
			current = map[ current.parent ];
		}
		return depth;
	}

	/* --------------------------------------------------------------------
	 * Tree rendering (sidebar)
	 * ------------------------------------------------------------------ */
	function buildBranch( parentId ) {
		var children = folders.filter( function ( f ) {
			return parseInt( f.parent, 10 ) === parseInt( parentId, 10 );
		} );

		if ( ! children.length ) {
			return '';
		}

		var html = '<ul role="group">';
		children.forEach( function ( f ) {
			html += renderNode( f );
		} );
		html += '</ul>';
		return html;
	}

	function renderNode( f ) {
		var active    = ( String( D.currentFolder ) === String( f.id ) );
		var childHtml = buildBranch( f.id );
		var expanded  = childHtml ? ' aria-expanded="true"' : '';
		var actions = D.canManage
			? '<span class="umfd-node__actions">' +
				'<button type="button" tabindex="-1" class="umfd-node__action umfd-action-add" aria-label="' + esc( S.addSubfolder ) + '" title="' + esc( S.addSubfolder ) + '"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span></button>' +
				'<button type="button" tabindex="-1" class="umfd-node__action umfd-action-rename" aria-label="' + esc( S.rename ) + '" title="' + esc( S.rename ) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>' +
				'<button type="button" tabindex="-1" class="umfd-node__action umfd-action-delete" aria-label="' + esc( S.delete ) + '" title="' + esc( S.delete ) + '"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>' +
			  '</span>'
			: '';

		return '<li class="umfd-node umfd-node--folder' + ( active ? ' is-active' : '' ) + '" data-id="' + f.id + '" role="treeitem" aria-selected="' + ( active ? 'true' : 'false' ) + '" tabindex="-1"' + expanded + '>' +
			'<div class="umfd-node__row" data-folder="' + f.id + '">' +
				'<span class="dashicons dashicons-portfolio umfd-node__icon" aria-hidden="true"></span>' +
				'<span class="umfd-node__label">' + esc( f.name ) + '</span>' +
				'<span class="umfd-node__count">' + parseInt( f.count, 10 ) + '</span>' +
				actions +
			'</div>' +
			childHtml +
		'</li>';
	}

	function renderTree() {
		var allActive   = ( D.currentFolder === '' );
		var uncatActive = ( D.currentFolder === 'uncategorized' );

		var html = '<ul class="umfd-tree" role="tree" aria-label="' + esc( S.foldersTitle ) + '">';

		html += '<li class="umfd-node umfd-node--all' + ( allActive ? ' is-active' : '' ) + '" data-id="all" role="treeitem" aria-selected="' + ( allActive ? 'true' : 'false' ) + '" tabindex="-1">' +
			'<div class="umfd-node__row" data-folder="">' +
				'<span class="dashicons dashicons-images-alt2 umfd-node__icon" aria-hidden="true"></span>' +
				'<span class="umfd-node__label">' + esc( S.allMedia ) + '</span>' +
				'<span class="umfd-node__count">' + parseInt( allCount, 10 ) + '</span>' +
			'</div>' +
		'</li>';

		html += '<li class="umfd-node umfd-node--uncategorized' + ( uncatActive ? ' is-active' : '' ) + '" data-id="uncategorized" role="treeitem" aria-selected="' + ( uncatActive ? 'true' : 'false' ) + '" tabindex="-1">' +
			'<div class="umfd-node__row" data-folder="uncategorized">' +
				'<span class="dashicons dashicons-category umfd-node__icon" aria-hidden="true"></span>' +
				'<span class="umfd-node__label">' + esc( S.uncategorized ) + '</span>' +
				'<span class="umfd-node__count">' + parseInt( uncatCount, 10 ) + '</span>' +
			'</div>' +
		'</li>';

		html += buildBranch( 0 ).replace( /^<ul[^>]*>/, '' ).replace( /<\/ul>$/, '' );
		html += '</ul>';

		if ( ! folders.length && D.canManage ) {
			html += '<p class="umfd-empty">' + esc( S.emptyState ) + '</p>';
		}

		$( '.umfd-tree-wrap' ).html( html );
		initRovingTabindex();
		bindDropTargets();
	}

	/* --------------------------------------------------------------------
	 * Keyboard accessibility (ARIA tree)
	 * ------------------------------------------------------------------ */
	function treeItems() {
		return $( '.umfd-tree [role="treeitem"]' ).filter( ':visible' );
	}

	function initRovingTabindex() {
		var $items = $( '.umfd-tree [role="treeitem"]' );
		if ( ! $items.length ) {
			return;
		}
		$items.attr( 'tabindex', '-1' );
		var $active = $items.filter( '.is-active' ).first();
		if ( ! $active.length ) {
			$active = $items.first();
		}
		$active.attr( 'tabindex', '0' );
	}

	function focusTreeItem( $item ) {
		if ( ! $item || ! $item.length ) {
			return;
		}
		$( '.umfd-tree [role="treeitem"]' ).attr( 'tabindex', '-1' );
		$item.attr( 'tabindex', '0' ).trigger( 'focus' );
	}

	function folderOfItem( $item ) {
		return String( $item.children( '.umfd-node__row' ).attr( 'data-folder' ) );
	}

	/* --------------------------------------------------------------------
	 * Sidebar shell
	 * ------------------------------------------------------------------ */
	function mountSidebar() {
		if ( ! D.isLibrary || $( '.umfd-sidebar' ).length ) {
			return;
		}

		var $wrap = $( '#wpbody-content > .wrap' ).first();
		if ( ! $wrap.length ) {
			return;
		}

		$( 'body' ).addClass( 'umfd-has-sidebar' );

		// Wrap existing content so we can sit beside it.
		var $content = $( '<div class="umfd-content-area" />' );
		$wrap.children().not( 'h1, .wp-heading-inline, .page-title-action, hr.wp-header-end' ).appendTo( $content );

		var sidebar =
			'<div class="umfd-sidebar">' +
				'<div class="umfd-sidebar__header">' +
					'<h2 class="umfd-sidebar__title">' + esc( S.foldersTitle ) + '</h2>' +
					( D.canManage ? '<button type="button" class="umfd-add-btn umfd-add-root"><span class="dashicons dashicons-plus-alt2"></span>' + esc( S.addFolder ) + '</button>' : '' ) +
				'</div>' +
				'<div class="umfd-newroot-wrap"></div>' +
				'<div class="umfd-tree-wrap"><div class="umfd-state"><span class="umfd-spinner"></span>' + esc( S.loading ) + '</div></div>' +
			'</div>';

		$wrap.append( sidebar ).append( $content );
		loadTree();
	}

	function loadTree() {
		request( 'umfd_get_tree' )
			.done( function ( res ) {
				if ( res && res.success ) {
					folders    = res.data.folders || [];
					allCount   = res.data.all_count || 0;
					uncatCount = res.data.uncategorized || 0;
					renderTree();
				} else {
					showTreeError( res && res.data ? res.data.message : S.error );
				}
			} )
			.fail( function () {
				showTreeError( S.error );
			} );
	}

	function showTreeError( msg ) {
		$( '.umfd-tree-wrap' ).html( '<div class="umfd-state umfd-state--error">' + esc( msg ) + '</div>' );
	}

	/* --------------------------------------------------------------------
	 * Filtering
	 * ------------------------------------------------------------------ */
	function applyFilter( folder ) {
		D.currentFolder = folder;

		if ( 'list' === D.currentMode ) {
			var url = new URL( D.libraryUrl, window.location.origin );
			url.searchParams.set( 'mode', 'list' );
			if ( folder === '' ) {
				url.searchParams.delete( 'umfd_folder' );
			} else {
				url.searchParams.set( 'umfd_folder', folder );
			}
			window.location.href = url.toString();
			return;
		}

		// Grid mode: re-query the on-page library in place.
		setGridFolder( folder );
		highlightActive( folder );
	}

	function highlightActive( folder ) {
		$( '.umfd-node' ).removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
		var $target;
		if ( folder === '' ) {
			$target = $( '.umfd-node--all' );
		} else if ( folder === 'uncategorized' ) {
			$target = $( '.umfd-node--uncategorized' );
		} else {
			$target = $( '.umfd-node[data-id="' + folder + '"]' );
		}
		$target.addClass( 'is-active' ).attr( 'aria-selected', 'true' );
	}

	function getGridLibrary() {
		if ( window.wp && wp.media && wp.media.frame && typeof wp.media.frame.state === 'function' ) {
			var state = wp.media.frame.state();
			if ( state && state.get( 'library' ) ) {
				return state.get( 'library' );
			}
		}
		return null;
	}

	// Clear wp.media's cached attachment queries so the next folder you open
	// fetches fresh data. We intentionally do NOT refetch the current view here
	// (that would re-render and pull a just-moved item back in).
	function clearMediaCache() {
		if ( window.wp && wp.media && wp.media.model && wp.media.model.Query ) {
			wp.media.model.Query.queries = [];
		}
	}

	// The folder currently being shown. In grid mode the live filter lives on
	// the media library props; fall back to the sidebar's tracked folder.
	function getActiveFolderFilter() {
		var lib = getGridLibrary();
		if ( lib && lib.props && typeof lib.props.get === 'function' ) {
			var f = lib.props.get( 'umfd_folder' );
			if ( typeof f !== 'undefined' && f !== null && '' !== f ) {
				return String( f );
			}
		}
		return String( D.currentFolder || '' );
	}

	function setGridFolder( folder ) {
		// Always fetch fresh results when switching folders.
		if ( window.wp && wp.media && wp.media.model && wp.media.model.Query ) {
			wp.media.model.Query.queries = [];
		}
		var attempts = 0;
		( function trySet() {
			var lib = getGridLibrary();
			if ( lib && lib.props ) {
				lib.props.set( 'umfd_folder', folder );
				if ( typeof lib._requery === 'function' ) {
					lib._requery( true );
				}
				return;
			}
			if ( attempts++ < 10 ) {
				window.setTimeout( trySet, 200 );
			}
		} )();
	}

	/* --------------------------------------------------------------------
	 * Folder CRUD
	 * ------------------------------------------------------------------ */
	function showInlineForm( $container, placeholder, current, onSubmit ) {
		$container.find( '.umfd-inline-form' ).remove();

		var checkSvg = '<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false">' +
			'<path d="M4 10.5 8 14.5 16 6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		var xSvg = '<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false">' +
			'<path d="M5 5 15 15 M15 5 5 15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>';

		var $form = $(
			'<form class="umfd-inline-form">' +
				'<input type="text" aria-label="' + esc( placeholder ) + '" placeholder="' + esc( placeholder ) + '" value="' + esc( current || '' ) + '" />' +
				'<button type="submit" class="button button-small button-primary umfd-inline-save" aria-label="' + esc( S.save ) + '" title="' + esc( S.save ) + '">' + checkSvg + '</button>' +
				'<button type="button" class="button button-small umfd-cancel" aria-label="' + esc( S.cancel ) + '" title="' + esc( S.cancel ) + '">' + xSvg + '</button>' +
			'</form>'
		);
		$container.prepend( $form );
		$form.find( 'input' ).trigger( 'focus' );

		$form.on( 'submit', function ( e ) {
			e.preventDefault();
			var val = $.trim( $form.find( 'input' ).val() );
			if ( '' === val ) {
				return;
			}
			onSubmit( val, $form );
		} );

		$form.find( '.umfd-cancel' ).on( 'click', function () {
			$form.remove();
		} );
	}

	function createFolder( parent, $context ) {
		showInlineForm( $context, S.promptName, '', function ( name, $form ) {
			$form.find( 'button' ).prop( 'disabled', true );
			request( 'umfd_create_folder', { name: name, parent: parent || 0 } )
				.done( function ( res ) {
					if ( res && res.success ) {
						folders.push( res.data.folder );
						renderTree();
					} else {
						toast( res && res.data ? res.data.message : S.error );
						$form.remove();
					}
				} )
				.fail( function () {
					toast( S.error );
					$form.remove();
				} );
		} );
	}

	function renameFolder( id, currentName, $node ) {
		showInlineForm( $node, S.promptRename, currentName, function ( name, $form ) {
			$form.find( 'button' ).prop( 'disabled', true );
			request( 'umfd_rename_folder', { folder_id: id, name: name } )
				.done( function ( res ) {
					if ( res && res.success ) {
						$.each( folders, function ( i, f ) {
							if ( parseInt( f.id, 10 ) === parseInt( id, 10 ) ) {
								f.name = res.data.folder.name;
							}
						} );
						renderTree();
					} else {
						toast( res && res.data ? res.data.message : S.error );
						$form.remove();
					}
				} )
				.fail( function () {
					toast( S.error );
					$form.remove();
				} );
		} );
	}

	function deleteFolder( id ) {
		if ( ! window.confirm( S.confirmDelete ) ) {
			return;
		}
		request( 'umfd_delete_folder', { folder_id: id } )
			.done( function ( res ) {
				if ( res && res.success ) {
					folders = folders.filter( function ( f ) {
						return parseInt( f.id, 10 ) !== parseInt( id, 10 );
					} );
					if ( String( D.currentFolder ) === String( id ) ) {
						D.currentFolder = '';
					}
					renderTree();
				} else {
					toast( res && res.data ? res.data.message : S.error );
				}
			} )
			.fail( function () {
				toast( S.error );
			} );
	}

	/* --------------------------------------------------------------------
	 * Drag & drop assignment
	 *
	 * We deliberately do NOT use jQuery UI droppable here. The folder tree is
	 * re-rendered after every change, which leaves stale droppable references
	 * in jQuery UI's manager and makes some targets silently stop accepting
	 * drops. Instead we track the folder row under the pointer ourselves with
	 * document.elementFromPoint(), so every target behaves identically.
	 * ------------------------------------------------------------------ */
	var umfdDropFolder = null; // String id, "uncategorized", or null.

	function clearDropHighlight() {
		$( '.umfd-node.is-droptarget' ).removeClass( 'is-droptarget' );
		umfdDropFolder = null;
	}

	function updateDropTarget( clientX, clientY ) {
		if ( typeof clientX !== 'number' || typeof clientY !== 'number' ) {
			return;
		}
		// The drag helper has pointer-events:none, so elementFromPoint sees
		// straight through it to the folder row underneath.
		var el   = document.elementFromPoint( clientX, clientY );
		var $row = el ? $( el ).closest( '.umfd-node__row[data-folder]' ) : $();

		if ( ! $row.length ) {
			clearDropHighlight();
			return;
		}

		var folder = String( $row.attr( 'data-folder' ) );

		// "All Media" (empty data-folder) is not a drop target.
		if ( '' === folder ) {
			clearDropHighlight();
			return;
		}

		if ( umfdDropFolder !== folder ) {
			$( '.umfd-node.is-droptarget' ).removeClass( 'is-droptarget' );
			$row.closest( '.umfd-node' ).addClass( 'is-droptarget' );
			umfdDropFolder = folder;
		}
	}

	function startDraggable( $el ) {
		if ( $el.data( 'umfd-drag' ) ) {
			return;
		}
		$el.data( 'umfd-drag', true );
		$el.css( 'cursor', 'grab' );

		$el.draggable( {
			helper: function () {
				return $( '<div class="umfd-drag-helper" />' ).text( '1 item' );
			},
			appendTo: 'body',
			cursorAt: { top: 10, left: 12 },
			scroll: false,
			distance: 8,
			zIndex: 100000,
			start: function () {
				umfdDropFolder = null;
			},
			drag: function ( event ) {
				updateDropTarget( event.clientX, event.clientY );
			},
			stop: function () {
				var target = umfdDropFolder;
				clearDropHighlight();
				if ( target ) {
					handleDrop( $el, target );
				}
			}
		} );
	}

	function makeMediaDraggable() {
		// List mode rows.
		$( '.wp-list-table.media tbody tr' ).each( function () {
			var $row = $( this );
			if ( $row.data( 'umfd-drag' ) ) {
				return;
			}
			var id = parseInt( $row.find( '.check-column input' ).val(), 10 );
			if ( ! id ) {
				return;
			}
			$row.attr( 'data-umfd-attachment', id );
			startDraggable( $row );
		} );

		// Grid mode thumbnails.
		$( '.attachments .attachment' ).each( function () {
			startDraggable( $( this ) );
		} );
	}

	function attachmentIdFromDraggable( $el ) {
		if ( $el.is( '[data-umfd-attachment]' ) ) {
			return parseInt( $el.attr( 'data-umfd-attachment' ), 10 );
		}
		// Grid: data-id attribute on .attachment.
		var id = $el.attr( 'data-id' );
		return id ? parseInt( id, 10 ) : 0;
	}

	// Drop targets are handled by the manual pointer tracking in
	// startDraggable()/updateDropTarget(); no jQuery UI droppable needed.
	function bindDropTargets() {}

	function handleDrop( $dragged, folder ) {
		var attId = attachmentIdFromDraggable( $dragged );
		if ( ! attId ) {
			return;
		}

		var isUncat = ( 'uncategorized' === folder );
		var source  = getActiveFolderFilter(); // The folder we are viewing now.
		var payload = { attachment_ids: [ attId ] };

		if ( isUncat ) {
			// Drop on Uncategorized = remove from every folder.
			payload.mode = 'remove';
		} else {
			// Drop on a folder = MOVE the item there. We replace the item's
			// folder membership so it ends up in exactly this one folder, with
			// no copy left behind in its previous folder. (Assigning an item to
			// multiple folders is still available from the attachment details
			// panel, where it is an explicit choice.)
			payload.mode      = 'set';
			payload.folder_id = folder;
		}

		request( 'umfd_assign_media', payload )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.updated > 0 ) {
					toast( S.droppedToFolder );
					loadTree();
					reflectMoveInView( $dragged, folder, isUncat, source );
				} else if ( res && res.success ) {
					// Server accepted the request but nothing changed (e.g. no
					// permission to edit that item).
					toast( S.noPermissionItem );
				} else {
					toast( res && res.data ? res.data.message : S.error );
				}
			} )
			.fail( function () { toast( S.error ); } );
	}

	// Reflect a completed move in whatever view is on screen.
	//
	// Grid mode: the media app owns rendering, so we clear its query cache and
	// re-fetch the current view ONCE. That shows the true server state (the item
	// is gone from the folder it left) with no fade that could flash back.
	//
	// List mode: there is no media app, so we fade the table row out if it no
	// longer matches the active filter.
	function reflectMoveInView( $dragged, targetFolder, isUncat, source ) {
		var lib = getGridLibrary();
		if ( lib ) {
			clearMediaCache();
			if ( typeof lib._requery === 'function' ) {
				lib._requery( true );
			}
			return;
		}

		var cur = String( source || '' );
		if ( '' === cur ) {
			return; // "All Media" – the item still belongs here.
		}
		var stillVisible = isUncat ? ( 'uncategorized' === cur ) : ( cur === String( targetFolder ) );
		if ( stillVisible ) {
			return;
		}
		var $row = $dragged.closest( 'tr' );
		if ( $row.length ) {
			$row.fadeOut( 180, function () { $( this ).remove(); } );
		}
	}

	/* --------------------------------------------------------------------
	 * Native grid / modal "Folders" dropdown filter
	 * ------------------------------------------------------------------ */
	function setupMediaFilter() {
		if ( ! window.wp || ! wp.media || ! wp.media.view || ! wp.media.view.AttachmentFilters ) {
			return;
		}

		// 1) Make the custom prop reach the server on every attachments query.
		var QuerySync = wp.media.model.Query.prototype.sync;
		wp.media.model.Query.prototype.sync = function ( method, model, options ) {
			if ( 'read' === method && this.args && typeof this.args.umfd_folder !== 'undefined' && this.args.umfd_folder !== '' ) {
				options = options || {};
				options.data = options.data || {};
				options.data.umfd_folder = this.args.umfd_folder;
			}
			return QuerySync.apply( this, arguments );
		};

		// 2) A filter dropdown listing All / Uncategorized / folders.
		var UMFFilters = wp.media.view.AttachmentFilters.extend( {
			id: 'umfd-media-folder-filter',
			createFilters: function () {
				var filters = {};
				filters.all = {
					text: S.allMedia,
					props: { umfd_folder: '' },
					priority: 5
				};
				filters.uncategorized = {
					text: S.uncategorized,
					props: { umfd_folder: 'uncategorized' },
					priority: 6
				};
				folders.forEach( function ( f, i ) {
					var prefix = new Array( depthOf( f.id ) + 1 ).join( '— ' );
					filters[ 'umfd_' + f.id ] = {
						text: prefix + f.name,
						props: { umfd_folder: String( f.id ) },
						priority: 10 + i
					};
				} );
				this.filters = filters;
			}
		} );

		// 3) Add the dropdown to every AttachmentsBrowser toolbar.
		//
		//    The folder filter is cosmetic. It must NEVER stop the toolbar from
		//    building, because on the grid Media Library screen the file
		//    uploader (drop zone + plupload) is wired up as part of this same
		//    view render. If our filter threw here, the whole frame render would
		//    abort and uploads would silently fail. So we always let the native
		//    toolbar build first, then add our filter inside a guard: any
		//    failure is swallowed and the media frame keeps working.
		var Browser = wp.media.view.AttachmentsBrowser;
		wp.media.view.AttachmentsBrowser = Browser.extend( {
			createToolbar: function () {
				Browser.prototype.createToolbar.call( this );

				try {
					if ( ! this.toolbar || ! this.collection || ! this.collection.props ) {
						return;
					}
					this.toolbar.set( 'umfdFolderFilter', new UMFFilters( {
						controller: this.controller,
						model: this.collection.props,
						priority: -75
					} ).render() );
				} catch ( e ) {
					if ( window.console && window.console.warn ) {
						window.console.warn( 'Unstoppable Media Folders: folder filter skipped.', e );
					}
				}
			}
		} );
	}

	/* --------------------------------------------------------------------
	 * Event delegation
	 * ------------------------------------------------------------------ */
	function bindEvents() {
		var $body = $( document );

		// Filter on folder click (ignore action buttons).
		$body.on( 'click', '.umfd-node__row', function ( e ) {
			if ( $( e.target ).closest( '.umfd-node__actions' ).length ) {
				return;
			}
			applyFilter( String( $( this ).data( 'folder' ) ) );
		} );

		// Add root folder.
		$body.on( 'click', '.umfd-add-root', function () {
			createFolder( 0, $( '.umfd-newroot-wrap' ) );
		} );

		// Add subfolder.
		$body.on( 'click', '.umfd-action-add', function ( e ) {
			e.stopPropagation();
			var $node = $( this ).closest( '.umfd-node' );
			createFolder( $node.data( 'id' ), $node );
		} );

		// Rename.
		$body.on( 'click', '.umfd-action-rename', function ( e ) {
			e.stopPropagation();
			var $node = $( this ).closest( '.umfd-node' );
			renameFolder( $node.data( 'id' ), $node.find( '.umfd-node__label' ).first().text(), $node );
		} );

		// Delete.
		$body.on( 'click', '.umfd-action-delete', function ( e ) {
			e.stopPropagation();
			deleteFolder( $( this ).closest( '.umfd-node' ).data( 'id' ) );
		} );

		// Keyboard navigation for the folder tree (ARIA tree pattern).
		$body.on( 'keydown', '.umfd-tree [role="treeitem"]', function ( e ) {
			var $item  = $( this );
			var $items = treeItems();
			var idx    = $items.index( $item );
			var handled = true;

			switch ( e.key ) {
				case 'ArrowDown':
					focusTreeItem( $items.eq( Math.min( idx + 1, $items.length - 1 ) ) );
					break;
				case 'ArrowUp':
					focusTreeItem( $items.eq( Math.max( idx - 1, 0 ) ) );
					break;
				case 'Home':
					focusTreeItem( $items.first() );
					break;
				case 'End':
					focusTreeItem( $items.last() );
					break;
				case 'ArrowRight':
					focusTreeItem( $item.find( '[role="treeitem"]' ).first() );
					break;
				case 'ArrowLeft':
					focusTreeItem( $item.parent().closest( '[role="treeitem"]' ) );
					break;
				case 'Enter':
				case ' ':
				case 'Spacebar':
					applyFilter( folderOfItem( $item ) );
					break;
				case 'F2':
					if ( D.canManage && $item.hasClass( 'umfd-node--folder' ) ) {
						renameFolder( $item.data( 'id' ), $item.find( '.umfd-node__label' ).first().text(), $item );
					}
					break;
				case 'Delete':
					if ( D.canManage && $item.hasClass( 'umfd-node--folder' ) ) {
						deleteFolder( $item.data( 'id' ) );
					}
					break;
				default:
					handled = false;
			}

			if ( handled ) {
				e.preventDefault();
			}
		} );
	}

	// Re-bind draggables when the media grid re-renders. Uses a MutationObserver
	// (the modern replacement for the deprecated DOMNodeInserted event and for
	// polling with setInterval). makeMediaDraggable() is idempotent.
	function observeMediaChanges() {
		makeMediaDraggable();

		if ( ! window.MutationObserver ) {
			return;
		}

		var target  = document.getElementById( 'wpbody-content' ) || document.body;
		var pending = null;
		var observer = new MutationObserver( function () {
			window.clearTimeout( pending );
			pending = window.setTimeout( makeMediaDraggable, 250 );
		} );
		observer.observe( target, { childList: true, subtree: true } );
	}

	/* --------------------------------------------------------------------
	 * Init
	 * ------------------------------------------------------------------ */
	$( function () {
		setupMediaFilter();
		mountSidebar();
		bindEvents();
		observeMediaChanges();
	} );

} )( jQuery );
