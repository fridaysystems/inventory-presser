window.addEventListener( 'load', function() {
	// Populate the slug as the name is typed into the Add New Location form.
	var tagName = this.document.querySelector( '#tag-name' );
	if ( tagName ) {
		tagName.addEventListener( 'input', function(){
			var tagSlug = document.querySelector('#tag-slug');
			if ( tagSlug ) {
				tagSlug.value = tagName.value.replaceAll( ' ', '-' ).replace( /[^-a-z0-9]/gi,'' ).toLowerCase();
			}
		});
	}

	// Hijack the built in taxonomy description for the location address.
	this.document.querySelector( '.term-description-wrap' ).style.display = 'none';
	this.document.querySelector( '#tag-description,#description' ).setAttribute( 'readonly', true );

	this.document.querySelectorAll( '.repeat-group' ).forEach( (group) => {
		// make sortable
		jQuery( group.querySelector( '.repeat-container' ) ).sortable(
			{
				handle: '.repeat-move',
				containment: 'parent'
			}
		);

		// initial setup - add a repeat group
		if ( 0 === group.querySelector( '.repeat-container' ).children.length ) {
			locationsDuplicateInputs( group );
		}

		// add buttons click event
		group.querySelector( '.repeat-add' ).addEventListener( 'click', function(e) {
			locationsDuplicateInputs( group );
		});
	});

	locationsBindDeleteButtons();

	jQuery( this.document.querySelectorAll( '.timepick' )).timepicker( { 'timeFormat': 'g:i A', 'scrollDefault': 'now' } );
});
function locationsBindDeleteButtons() {
	document.querySelectorAll( '.repeat-group .repeat-delete' ).forEach( ( el ) => {
		el.removeEventListener( 'click', locationsDeleteHandler );
		el.addEventListener( 'click', locationsDeleteHandler );
	});
}
function locationsDeleteHandler(evt) {
	var group     = evt.target.closest( '.repeat-group' );
	var container = evt.target.closest( '.repeat-container' );
	evt.target.closest( '.repeated' ).remove();
	// if there are no repeat groups, add a fresh one
	if ( 0 === container.children.length ) {
		locationsDuplicateInputs( group );
	}
	jQuery( container ).sortable( 'refresh' );
}
function locationsDuplicateInputs( group ) {
	var original = group.querySelector('.repeat-this');
	var newGroup = original.cloneNode( true );
	newGroup.classList.remove( 'repeat-this' );
	newGroup.classList.add( 'repeated' );
	var container = group.querySelector( '.repeat-container' );
	container.append( newGroup );
	locationsBindDeleteButtons();
}