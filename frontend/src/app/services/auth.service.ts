import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { finalize, from, Observable, tap, timeout } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly apiUrl = '/api';
  private readonly tokenKey = 'elit_dent_api_token';

  constructor(private http: HttpClient) {}

  login(data: { email: string; lozinka: string }): Observable<any> {
    return from(this.loginWithFetch(data)).pipe(timeout(10000));
  }

  register(data: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/register`, data).pipe(
      timeout(10000),
      tap((response) => this.saveToken(response.token))
    );
  }

  logout(): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/logout`, {}).pipe(
      finalize(() => localStorage.removeItem(this.tokenKey))
    );
  }

  me(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/me`);
  }

  saveToken(token: string): void {
    localStorage.setItem(this.tokenKey, token);
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  private async loginWithFetch(data: { email: string; lozinka: string }): Promise<any> {
    const response = await fetch(`${this.apiUrl}/login`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    const payload = await response.json();

    if (!response.ok) {
      throw { status: response.status, error: payload };
    }

    this.saveToken(payload.token);

    return payload;
  }
}
