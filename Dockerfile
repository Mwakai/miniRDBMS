FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Create storage directory
RUN mkdir -p /var/data/tables

# Set environment variable for storage path
ENV STORAGE_PATH=/var/data

# Expose port (Render sets PORT env variable)
EXPOSE 10000

# Start PHP built-in server
CMD php -S 0.0.0.0:${PORT:-10000} -t web
