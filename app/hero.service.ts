import { Injectable }    from '@angular/core';
import { Headers, Http } from '@angular/http';

import 'rxjs/add/operator/toPromise';

import { Hero } from './hero';


@Injectable()

export class HeroService {

	private heroesUrl = 'http://my.proloop.com/api/heroes';  // URL to web api 

	constructor(private http: Http) { }


	getHeroes(): Promise<Hero[]> {
  		return this.http.get(this.heroesUrl)
               .toPromise()
               .then(response => response.json().data as Hero[] )
               .catch(this.handleError);
	}

	getHero(id: number): Promise<Hero> {
		return this.getHeroes().then(heroes => heroes.find(hero => hero.id === id));
	}
}