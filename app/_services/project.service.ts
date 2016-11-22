import { Injectable } from '@angular/core';
import { Http, Headers, RequestOptions, Response } from '@angular/http';

import 'rxjs/add/operator/toPromise';

@Injectable()
export class ProjectService {
    constructor(private http: Http) { }

    getAll()  {
        return this.http.get('/api/projects', this.jwt()).map((response: Response) => response.json());
    }

    getById(id) {
        return this.http.get('/api/projects/' + id, this.jwt()).map((response: Response) => response.json());
    }

    create(user) {
        return this.http.post('/api/projects', user, this.jwt()).map((response: Response) => response.json());
    }

    update(user) {
        return this.http.put('/api/projects/' + user.id, user, this.jwt()).map((response: Response) => response.json());
    }

    delete(id) {
        return this.http.delete('/api/projects/' + id, this.jwt()).map((response: Response) => response.json());
    }

    // private helper methods

    private jwt() {
        // create authorization header with jwt token
        let currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (currentUser && currentUser.token) {
            let headers = new Headers({ 'Authorization': 'Bearer ' + currentUser.token });
            return new RequestOptions({ headers: headers });
        }
    }
}