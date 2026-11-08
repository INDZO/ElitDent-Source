import { DEMO_MODE, bookingDemo } from "../demo-mode";
import { Injectable } from '@angular/core';
import { from, Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class BookingService {
  private readonly apiUrl = '/api';
  private servicesRequest?: Promise<any>;
  private readonly teamsRequests = new Map<string, Promise<any>>();
  private readonly daysRequests = new Map<string, Promise<any>>();

  private authHeaders(): HeadersInit {
    const token = localStorage.getItem('elit_dent_api_token');

    return {
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };
  }

  private fetchJson(url: string, options: RequestInit = {}): Promise<any> {
    if (DEMO_MODE) return bookingDemo(url, options);
    return fetch(url, {
      ...options,
      headers: {
        ...this.authHeaders(),
        ...(options.headers || {}),
      },
    }).then(async (response) => {
      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw { status: response.status, error: data };
      }

      return data;
    });
  }

  getServices(): Observable<any> {
    this.servicesRequest ??= this.fetchJson(`${this.apiUrl}/patient/booking/services`).catch((error) => {
      this.servicesRequest = undefined;
      throw error;
    });

    return from(this.servicesRequest);
  }

  getTeams(serviceId: string): Observable<any> {
    const params = new URLSearchParams({ service_id: serviceId });
    const key = params.toString();
    let request = this.teamsRequests.get(key);

    if (!request) {
      request = this.fetchJson(`${this.apiUrl}/patient/booking/teams?${key}`).catch((error) => {
        this.teamsRequests.delete(key);
        throw error;
      });
      this.teamsRequests.set(key, request);
    }

    return from(request);
  }

  getDays(serviceId: string, teamId: string): Observable<any> {
    const params = new URLSearchParams({ service_id: serviceId, team_id: teamId });
    const key = params.toString();
    let request = this.daysRequests.get(key);

    if (!request) {
      request = this.fetchJson(`${this.apiUrl}/patient/booking/days?${key}`).catch((error) => {
        this.daysRequests.delete(key);
        throw error;
      });
      this.daysRequests.set(key, request);
    }

    return from(request);
  }

  getSlots(serviceId: string, teamId: string, date: string): Observable<any> {
    const params = new URLSearchParams({ service_id: serviceId, team_id: teamId, date });

    return from(this.fetchJson(`${this.apiUrl}/patient/booking/slots?${params.toString()}`));
  }

  bookAppointment(data: any): Observable<any> {
    return from(
      this.fetchJson(`${this.apiUrl}/patient/booking`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      }).then((response) => {
        this.daysRequests.clear();
        return response;
      })
    );
  }
}
