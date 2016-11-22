﻿import { Routes, RouterModule } from '@angular/router';

import { HomeComponent } from './home/index';
import { LoginComponent } from './login/index';
import { RegisterComponent } from './register/index';
import { AuthGuard } from './_guards/index';
import { ProjectsComponent } from './projects/index';
import { OrganizationsComponent } from './organizations/index';
import { MattersComponent } from './matters/index';

const appRoutes: Routes = [
    { path: '', component: HomeComponent, canActivate: [AuthGuard] },
    { path: 'login', component: LoginComponent },
    { path: 'register', component: RegisterComponent, canActivate: [AuthGuard]},
    { path: 'projects', component: ProjectsComponent, canActivate: [AuthGuard] },
    { path: 'organizations', component: OrganizationsComponent, canActivate: [AuthGuard] },
    { path: 'matters', component: MattersComponent, canActivate: [AuthGuard] },

    // otherwise redirect to home
    { path: '**', redirectTo: '' }
];

export const routing = RouterModule.forRoot(appRoutes);