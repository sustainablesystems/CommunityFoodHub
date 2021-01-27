//
// new SortingTable( 'my_table', {
//   zebra: true,     // Stripe the table, also on initialize
//   details: false   // Has details every other row
// });
//
// The above were the defaults.  The regexes in load_conversions test a cell
// begin sorted for a match, then use that conversion for all elements on that
// column.
//
// Requires mootools Class, Array, Function, Element, Element.Selectors,
// Element.Event, and you should probably get Window.DomReady if you're smart.
//

var SortingTable = new Class({

  initialize: function( table, options ) {
    this.options = $merge({
      zebra: true,
      details: false
    }, options);
    
    this.table = $(table);
    
    this.tbody = $(this.table.getElementsByTagName('tbody')[0]);
    if (this.options.zebra) {
      SortingTable.stripe_table( this.tbody.getElements( 'tr' ) );
    }

    this.headers = new Hash;
    var thead = $(this.table.getElementsByTagName('thead')[0]);
    $each(thead.getElementsByTagName('tr')[0].getElementsByTagName('th'), function( header, index ) {
      var header = $(header);
      this.headers.set( header.getText(), { column: index } );
      header.addEvent( 'mousedown', function(evt){
        var evt = new Event(evt);
        this.sort_by_header( evt.target.getText() );
      }.bind( this ) );
    }.bind( this ) );

    this.load_conversions();
  },

  sort_by_header: function( header_text ){
    this.rows = new Array;
    var trs = this.tbody.getElements( 'tr' );
    while ( trs.length > 0 ) {
      var row = { row: trs.shift().remove() };
      if ( this.options.details ) {
        row.detail = trs.shift().remove();
      }
      this.rows.unshift( row );
    }

    var header = this.headers.get( header_text );
    if ( this.sort_column >= 0 && this.sort_column == header.column ) {
      // They were pulled off in reverse
    } else {
      this.sort_column = header.column;
      if (header.conversion_function) {
        this.conversion_function = header.conversion_function;
      } else {
        this.conversion_function = false;
        this.rows.some(function(row){
          var to_match = $(row.row.getElementsByTagName('td')[this.sort_column]).getText();
          if (to_match == ''){ return false }
          this.conversions.some(function(conversion){
            if (conversion.matcher.test( to_match )){
              this.conversion_function = conversion.conversion_function;
              return true;
            }
            return false;
          }.bind( this ));
          if (this.conversion_function){ return true; }
          return false;
        }.bind( this ));
        header.conversion_function = this.conversion_function.bind( this );
        this.headers.set( header_text, header );
      }
      this.rows.each(function(row){
        row.compare_value = this.conversion_function( row );
      }.bind( this ));
      this.rows.sort( this.compare_rows.bind( this ) );
    }

    var index = 0;
    while (this.rows.length > 0) {
      var row = this.rows.shift();
      row.row.injectInside( this.tbody );
      if (row.detail){ row.detail.injectInside( this.tbody ) };
      if ( this.options.zebra ) {
        row.row.removeClass( 'alt' );
        if (row.detail){ row.detail.removeClass( 'alt' ); }
        if ( ( index % 2 ) == 0 ) {
          row.row.addClass( 'alt' );
          if (row.detail){ row.detail.addClass( 'alt' ); }
        }
      }
      index++;
    }
    this.rows = false;
  },

  compare_rows: function( r1, r2 ) {
    if ( r1.compare_value > r2.compare_value ) { return  1 }
    if ( r1.compare_value < r2.compare_value ) { return -1 }
    return 0;
  },
  
  load_conversions: function() {
    this.conversions = $A([
      // YYYY-MM-DD, YYYY-m-d
      { matcher: /\d{4}-\d{1,2}-\d{1,2}/,
        conversion_function: function( row ) {
          var cell = $(row.row.getElementsByTagName('td')[this.sort_column]).getText();
          var re = /(\d{4})-(\d{1,2})-(\d{1,2})/;
          cell = re.exec( cell );
          return new Date(parseInt(cell[1]), parseInt(cell[2], 10) - 1, parseInt(cell[3], 10));
        }
      },
      // "ddd, DD mmm YYYY" and "ddd, D mmm YYYY"
      { matcher: /(Mon|Tue|Wed|Thu|Fri|Sat|Sun), \d{1,2} (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{4}/,
        conversion_function: function( row ) {
          var cell = $(row.row.getElementsByTagName('td')[this.sort_column]).getText();
          return new Date(cell); // Javascript will convert the date string correctly for us
        }
      },
      
      // Fallback 
      { matcher: /.*/,
        conversion_function: function( row ) {
          return $(row.row.getElementsByTagName('td')[this.sort_column]).getText().toLowerCase();
        }
      }
    ]);
  }

});

SortingTable.stripe_table = function ( tr_elements  ) {
  var counter = 0;
  $$( tr_elements ).each( function( tr ) {
    if ( tr.style.display != 'none' && !tr.hasClass('collapsed') ) {
      counter++;
    }
    tr.removeClass( 'alt' );   
    if ( !(( counter % 2 ) == 0) ) {
      tr.addClass( 'alt' );   
    }
  }.bind( this ));
}

