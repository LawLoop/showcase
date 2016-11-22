"use strict";
var router_1 = require('@angular/router');
var index_1 = require('./home/index');
var index_2 = require('./login/index');
var index_3 = require('./register/index');
var index_4 = require('./_guards/index');
var index_5 = require('./projects/index');
var index_6 = require('./organizations/index');
var index_7 = require('./matters/index');
var appRoutes = [
    { path: '', component: index_1.HomeComponent, canActivate: [index_4.AuthGuard] },
    { path: 'login', component: index_2.LoginComponent },
    { path: 'register', component: index_3.RegisterComponent, canActivate: [index_4.AuthGuard] },
    { path: 'projects', component: index_5.ProjectsComponent, canActivate: [index_4.AuthGuard] },
    { path: 'organizations', component: index_6.OrganizationsComponent, canActivate: [index_4.AuthGuard] },
    { path: 'matters', component: index_7.MattersComponent, canActivate: [index_4.AuthGuard] },
    // otherwise redirect to home
    { path: '**', redirectTo: '' }
];
exports.routing = router_1.RouterModule.forRoot(appRoutes);
//# sourceMappingURL=app.routing.js.map