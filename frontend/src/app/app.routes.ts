import { DEMO_MODE } from "./demo-mode";
import { Routes } from '@angular/router';

import { BookingComponent } from './components/booking/booking.component';
import { LoginComponent } from './components/login/login.component';
import { RegisterComponent } from './components/register/register.component';

export const routes: Routes = DEMO_MODE ? [
  { path: "booking", component: BookingComponent },
  { path: "**", redirectTo: "booking" },
] : [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterComponent },
  { path: 'booking', component: BookingComponent },
];
