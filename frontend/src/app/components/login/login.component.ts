import { ChangeDetectorRef, Component, inject, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './login.component.html',
})
export class LoginComponent implements OnInit {
  private fb = inject(FormBuilder);
  private router = inject(Router);
  private cdr = inject(ChangeDetectorRef);

  error = '';
  loading = false;
  submitted = false;
  showPassword = false;

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    lozinka: ['', [Validators.required]],
  });

  ngOnInit(): void {
    if (new URLSearchParams(window.location.search).has('logged_out')) {
      localStorage.removeItem('elit_dent_api_token');
      window.history.replaceState({}, '', '/app/#/login');
    }
  }

  async submit(): Promise<void> {
    this.error = '';
    this.submitted = true;

    if (this.form.invalid) {
      this.error = 'Unesite email i lozinku.';
      this.form.markAllAsTouched();
      return;
    }

    this.loading = true;
    this.cdr.detectChanges();

    try {
      const response = await fetch('/api/login', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(this.form.getRawValue()),
      });

      const payload = await response.json();

      if (!response.ok) {
        this.error = payload?.message || 'Prijava nije uspela.';
        return;
      }

      localStorage.setItem('elit_dent_api_token', payload.token);

      if (payload?.web_redirect_url) {
        window.location.href = payload.web_redirect_url;
        return;
      }

      if (payload?.user?.Role !== 'pacijent') {
        this.error = 'Ova rola nema Angular pacijent stranicu.';
        return;
      }

      await this.router.navigateByUrl('/booking');
    } catch {
      this.error = 'Prijava nije uspela.';
    } finally {
      this.loading = false;
      this.cdr.detectChanges();
    }
  }

  togglePassword(): void {
    this.showPassword = !this.showPassword;
  }

  fieldInvalid(field: 'email' | 'lozinka'): boolean {
    const control = this.form.controls[field];
    return control.invalid && (control.touched || this.submitted);
  }

  fieldError(field: 'email' | 'lozinka'): string {
    const control = this.form.controls[field];

    if (!this.fieldInvalid(field)) {
      return '';
    }

    if (control.errors?.['required']) {
      return field === 'email' ? 'Email je obavezan.' : 'Lozinka je obavezna.';
    }

    if (control.errors?.['email']) {
      return 'Unesite validnu email adresu.';
    }

    return 'Polje nije ispravno.';
  }
}
