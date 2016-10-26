/*
(function(app) {
  app.AppModule =
    ng.core.NgModule({
      imports: [ ng.platformBrowser.BrowserModule ],
      declarations: [ app.AppComponent ],
      bootstrap: [ app.AppComponent ]
    })
    .Class({
      constructor: function() {}
    });
})(window.app || (window.app = {}));
*/


import { NgModule }      from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { FormsModule }   from '@angular/forms';

import { AppComponent }   from './app.component';

@NgModule({
  imports:      [ 
  					BrowserModule, 
  					FormsModule 
  				],
  declarations: [ 
  					AppComponent 
  				],
  bootstrap:    [ 
  					AppComponent 
  				]
})
export class AppModule { }
