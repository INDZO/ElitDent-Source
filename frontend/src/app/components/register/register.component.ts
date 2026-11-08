import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './register.component.html',
})
export class RegisterComponent {
  private fb = inject(FormBuilder);
  private auth = inject(AuthService);
  private router = inject(Router);

  errors: string[] = [];
  loading = false;
  submitted = false;
  showPassword = false;
  showConfirmPassword = false;
  readonly maxBirthDate = this.yesterday();

  form = this.fb.group({
    ime: ['', [Validators.required, Validators.maxLength(30)]],
    prezime: ['', [Validators.required, Validators.maxLength(40)]],
    email: ['', [Validators.required, Validators.email]],
    lozinka: ['', [Validators.required, Validators.minLength(8)]],
    lozinka_confirmation: ['', [Validators.required]],
    datum_rodjenja: ['', [Validators.required]],
    telefon: ['', [Validators.required]],
    adresa: ['', [Validators.required]],
    alergije: [''],
    hronicne_bolesti: [''],
    terapije: [''],
  });

  submit(): void {
    this.errors = [];
    this.submitted = true;

    if (this.form.controls.lozinka.value !== this.form.controls.lozinka_confirmation.value) {
      this.errors = ['Potvrda lozinke mora da se poklapa sa lozinkom.'];
      this.form.controls.lozinka_confirmation.setErrors({ mismatch: true });
    }

    if (this.form.invalid || this.errors.length > 0) {
      this.form.markAllAsTouched();
      if (this.errors.length === 0) {
        this.errors = ['Popunite obavezna polja ispravnim vrednostima.'];
      }
      return;
    }

    this.loading = true;
    this.auth.register(this.form.getRawValue()).subscribe({
      next: (response) => {
        if (response?.web_redirect_url) {
          window.location.href = response.web_redirect_url;
          return;
        }

        this.router.navigateByUrl('/login');
      },
      error: (error) => {
        this.errors = this.flattenErrors(error?.error?.errors) || [error?.error?.message || 'Registracija nije uspela.'];
        this.loading = false;
      },
    });
  }

  togglePassword(): void {
    this.showPassword = !this.showPassword;
  }

  toggleConfirmPassword(): void {
    this.showConfirmPassword = !this.showConfirmPassword;
  }

  passwordLength(): number {
    return this.form.controls.lozinka.value?.length || 0;
  }

  fieldInvalid(field: keyof typeof this.form.controls): boolean {
    const control = this.form.controls[field];
    return control.invalid && (control.touched || this.submitted);
  }

  fieldError(field: keyof typeof this.form.controls): string {
    const control = this.form.controls[field];

    if (!this.fieldInvalid(field)) {
      return '';
    }

    if (control.errors?.['required']) {
      return 'Ovo polje je obavezno.';
    }

    if (control.errors?.['email']) {
      return 'Unesite validnu email adresu.';
    }

    if (control.errors?.['maxlength']) {
      return `Maksimalno ${control.errors['maxlength'].requiredLength} karaktera.`;
    }

    if (control.errors?.['minlength']) {
      return 'Lozinka mora imati najmanje 8 karaktera.';
    }

    if (control.errors?.['mismatch']) {
      return 'Potvrda lozinke se ne poklapa.';
    }

    return 'Polje nije ispravno.';
  }

  private flattenErrors(errors: Record<string, string[]> | undefined): string[] | null {
    if (!errors) {
      return null;
    }

    return Object.values(errors).flat();
  }

  private yesterday(): string {
    const date = new Date();
    date.setDate(date.getDate() - 1);
    return date.toISOString().slice(0, 10);
  }
}
