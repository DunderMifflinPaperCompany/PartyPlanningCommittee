// Belsnickel insists the JavaScript suite run the same way for everyone, exactly as phpunit.xml does.
import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
        coverage: {
            provider: 'v8',
            include: ['public/assets/js/**/*.js'],
            reportsDirectory: 'build/coverage-js',
            reporter: ['text', 'cobertura'],
        },
    },
});
